<?php

namespace App\Services;

use App\Models\Proposal;
use Illuminate\Support\Collection;

/**
 * Finds prior proposals similar to a given proposal, and detects
 * overlapping deliverable names to prevent duplicate scope entries.
 */
class ProposalSimilarityService
{
    /**
     * Find proposals similar to $proposal, ranked by similarity score (descending).
     * Excludes the proposal itself. Returns a collection of arrays:
     *   [proposal, score, match_reasons, deliverable_overlap]
     */
    public function findSimilar(Proposal $proposal, int $limit = 8): Collection
    {
        $candidates = Proposal::with(['company', 'sector', 'workType', 'projectType', 'status',
                                      'deliverables.activities.tasks', 'accountManager'])
            ->where('id', '!=', $proposal->id)
            ->orderByDesc('year')
            ->orderByDesc('proposal_number')
            ->get();

        $currentDeliverableNames = $this->extractDeliverableNames($proposal);

        return $candidates
            ->map(function (Proposal $candidate) use ($proposal, $currentDeliverableNames) {
                [$score, $reasons] = $this->scoreProposal($proposal, $candidate);
                $overlap = $this->getDeliverableOverlap($currentDeliverableNames, $candidate);

                return [
                    'proposal'            => $candidate,
                    'score'               => $score,
                    'match_reasons'       => $reasons,
                    'deliverable_overlap' => $overlap,
                ];
            })
            ->filter(fn ($r) => $r['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * Score a candidate proposal against the current one (0–100).
     * Returns [int $score, array $reasons].
     */
    public function scoreProposal(Proposal $current, Proposal $candidate): array
    {
        $score   = 0;
        $reasons = [];

        // Same client company (+40)
        if ($current->company_id && $current->company_id === $candidate->company_id) {
            $score += 40;
            $reasons[] = ['type' => 'company', 'label' => 'Same client: ' . ($current->company?->name ?? '')];
        }

        // Same sector (+20)
        if ($current->sector_id && $current->sector_id === $candidate->sector_id) {
            $score += 20;
            $reasons[] = ['type' => 'sector', 'label' => 'Same sector: ' . ($current->sector?->name ?? '')];
        }

        // Same work type (+20)
        if ($current->work_type_id && $current->work_type_id === $candidate->work_type_id) {
            $score += 20;
            $reasons[] = ['type' => 'work_type', 'label' => 'Same work type: ' . ($current->workType?->name ?? '')];
        }

        // Same project type (+10)
        if ($current->project_type_id && $current->project_type_id === $candidate->project_type_id) {
            $score += 10;
            $reasons[] = ['type' => 'project_type', 'label' => 'Same project type: ' . ($current->projectType?->name ?? '')];
        }

        // Similar contract value (within 50%) (+5)
        $currentVal   = $current->contract_value   ?? $current->total_fee   ?? 0;
        $candidateVal = $candidate->contract_value ?? $candidate->total_fee ?? 0;
        if ($currentVal > 0 && $candidateVal > 0) {
            $ratio = min($currentVal, $candidateVal) / max($currentVal, $candidateVal);
            if ($ratio >= 0.5) {
                $score += 5;
                $reasons[] = ['type' => 'fee', 'label' => 'Similar contract value'];
            }
        }

        // Deliverable name overlap (+5 per match, max +15)
        $overlap = $this->getDeliverableOverlap(
            $this->extractDeliverableNames($current),
            $candidate
        );
        if (count($overlap['matched']) > 0) {
            $bonus = min(15, count($overlap['matched']) * 5);
            $score += $bonus;
            $reasons[] = ['type' => 'deliverables', 'label' => count($overlap['matched']) . ' overlapping deliverable(s)'];
        }

        return [$score, $reasons];
    }

    /**
     * Compare current proposal's deliverable names against a candidate proposal.
     * Returns ['matched' => [...], 'candidate_only' => [...], 'similarity_pct' => int]
     */
    public function getDeliverableOverlap(array $currentNames, Proposal $candidate): array
    {
        $candidateNames = $this->extractDeliverableNames($candidate);
        $matched        = [];
        $candidateOnly  = [];

        foreach ($candidateNames as $candName) {
            $found = false;
            foreach ($currentNames as $currName) {
                if ($this->namesAreSimilar($currName, $candName)) {
                    $matched[] = $candName;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $candidateOnly[] = $candName;
            }
        }

        $total  = count($candidateNames);
        $simPct = $total > 0 ? (int) round((count($matched) / $total) * 100) : 0;

        return [
            'matched'        => array_unique($matched),
            'candidate_only' => $candidateOnly,
            'similarity_pct' => $simPct,
        ];
    }

    /**
     * Check which deliverable names from a source proposal already exist
     * (or are highly similar) in a target proposal. Used before import.
     * Returns ['duplicates' => [...names...], 'new' => [...names...]]
     */
    public function checkImportDuplicates(Proposal $target, Proposal $source): array
    {
        $targetNames = $this->extractDeliverableNames($target);
        $duplicates  = [];
        $new         = [];

        foreach ($this->extractDeliverableNames($source) as $srcName) {
            $isDuplicate = false;
            foreach ($targetNames as $tgtName) {
                if ($this->namesAreSimilar($tgtName, $srcName)) {
                    $isDuplicate = true;
                    break;
                }
            }
            if ($isDuplicate) {
                $duplicates[] = $srcName;
            } else {
                $new[] = $srcName;
            }
        }

        return ['duplicates' => $duplicates, 'new' => $new];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function extractDeliverableNames(Proposal $proposal): array
    {
        // Load deliverables if not already loaded
        if (!$proposal->relationLoaded('deliverables')) {
            $proposal->load('deliverables');
        }

        return $proposal->deliverables->pluck('name')->filter()->map(fn ($n) => strtolower(trim($n)))->values()->all();
    }

    /**
     * Two names are "similar" if their normalized forms match or
     * PHP's similar_text() yields >= 75% similarity.
     */
    private function namesAreSimilar(string $a, string $b): bool
    {
        $a = strtolower(trim($a));
        $b = strtolower(trim($b));

        if ($a === $b) {
            return true;
        }

        similar_text($a, $b, $pct);

        return $pct >= 75.0;
    }
}
