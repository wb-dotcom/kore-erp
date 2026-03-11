<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectNote;
use Illuminate\Http\Request;

class ProjectNoteController extends Controller
{
    /**
     * POST /projects/{project}/notes
     */
    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'content'        => ['required', 'string'],
            'is_todo'        => ['boolean'],
            'due_date'       => ['nullable', 'date'],
            'priority_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $note = $project->notes()->create([
            'user_id'        => auth()->id(),
            'content'        => $data['content'],
            'is_todo'        => $data['is_todo'] ?? false,
            'is_done'        => false,
            'due_date'       => $data['due_date'] ?? null,
            'priority_order' => $data['priority_order'] ?? 100,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'note' => $this->noteJson($note)]);
        }

        return back()->with('success', 'Note added.');
    }

    /**
     * PUT /projects/{project}/notes/{note}
     */
    public function update(Request $request, Project $project, ProjectNote $note)
    {
        $this->authorizeNote($note);

        $data = $request->validate([
            'content'        => ['nullable', 'string'],
            'is_todo'        => ['nullable', 'boolean'],
            'due_date'       => ['nullable', 'date'],
            'priority_order' => ['nullable', 'integer', 'min:1'],
        ]);

        $note->update(array_filter($data, fn($v) => $v !== null));

        return response()->json(['success' => true]);
    }

    /**
     * PATCH /projects/{project}/notes/{note}/toggle
     * Toggle is_done for a todo item.
     */
    public function toggle(Request $request, Project $project, ProjectNote $note)
    {
        $this->authorizeNote($note);

        $note->update(['is_done' => ! $note->is_done]);

        return response()->json(['success' => true, 'is_done' => $note->is_done]);
    }

    /**
     * DELETE /projects/{project}/notes/{note}
     */
    public function destroy(Project $project, ProjectNote $note)
    {
        $this->authorizeNote($note);
        $note->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Note deleted.');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function authorizeNote(ProjectNote $note): void
    {
        $user = auth()->user();
        if ($note->user_id !== $user->id && ! $user->isManager()) {
            abort(403);
        }
    }

    private function noteJson(ProjectNote $note): array
    {
        return [
            'id'             => $note->id,
            'content'        => $note->content,
            'is_todo'        => $note->is_todo,
            'is_done'        => $note->is_done,
            'due_date'       => $note->due_date?->format('Y-m-d'),
            'priority_order' => $note->priority_order,
            'author'         => $note->user?->full_name ?? auth()->user()->full_name,
            'created_at'     => $note->created_at?->format('M j, Y'),
        ];
    }
}
