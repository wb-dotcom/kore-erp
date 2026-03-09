<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-600">Reject Request</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="mb-2" style="font-size:0.82rem;">Rejecting request for <strong id="rejectName"></strong>.</p>
                    <label class="form-label" style="font-size:0.78rem;">Comments (optional)</label>
                    <textarea name="comments" class="form-control form-control-sm" rows="3"
                        placeholder="Reason for rejection..."></textarea>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('rejectModal').addEventListener('show.bs.modal', function(e) {
    const btn  = e.relatedTarget;
    const type = btn.dataset.type;
    const id   = btn.dataset.id;
    const name = btn.dataset.name;
    document.getElementById('rejectName').textContent = name;
    document.getElementById('rejectForm').action = `/approvals/${type}/${id}/reject`;
});
</script>
@endpush
