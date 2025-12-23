<!-- Ask AI Modal and Context Menu Partial -->
<div id="askAiContextMenu" class="dropdown-menu" style="display:none; position:absolute; z-index:9999;">
    <a class="dropdown-item" href="#" id="askAiMenuItem">
        <i class="fas fa-robot"></i> Ask AI about this
    </a>
</div>

<div class="modal fade" id="askAiModal" tabindex="-1" aria-labelledby="askAiModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="askAiModalLabel"><i class="fas fa-robot"></i> Ask AI</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="askAiForm">
          <div class="mb-3">
            <label for="askAiQuestion" class="form-label">Your question</label>
            <textarea class="form-control" id="askAiQuestion" rows="3" required></textarea>
            <input type="hidden" id="askAiContext" />
          </div>
          <div id="askAiResponse" class="alert alert-info d-none"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" id="askAiSubmitBtn">Ask AI</button>
      </div>
    </div>
  </div>
</div>
