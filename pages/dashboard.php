<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    body {
      background-color: #f8f9fa; /* Light background like your screenshot */
      font-family: 'Arial', sans-serif;
    }
    .header-container {
      background-color: #fff;
      padding: 20px 30px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #dee2e6;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .header-title {
      font-weight: 600;
      font-size: 1.25rem;
    }
    .header-subtitle {
      font-size: 0.875rem;
      color: #6c757d;
      margin-top: 2px;
    }
    .tab-btn.active {
        background-color: white;
        border: none !important;
    }

    .tab-btn.active:hover {
        background-color: white;
        border: none !important;
    }
    .tab-btn {
        border: none !important;
    }
    .tab-btn:hover {
        background-color: rgb(225,228,232);
        border: none !important;
    }
    .new-btn {
        background-color: rgb(15,62,69);
        color: white;
    }
    .new-btn:hover {
        background-color: rgba(15, 62, 69, 0.9);
        color: white;
    }
  </style>
</head>
<body>

    <div class="header-container ps-5 pe-5">
    <div>
      <div class="header-title">Engagement Tracker</div>
      <div class="header-subtitle">Manage your audit engagements</div>
    </div>
    <div class="header-actions d-flex align-items-center">
        <div class="view-options p-1" style="background-color: rgb(241,242,245); border-radius: 10px;">
            <button type="button" class="btn btn-sm me-2 tab-btn active"><i class="bi bi-grid"></i>&nbsp;&nbsp;Board</button>
            <button type="button" class="btn btn-sm me-2 tab-btn"><i class="bi bi-list-ul"></i>&nbsp;&nbsp;List</button>
            <button type="button" class="btn btn-sm me-2 tab-btn"><i class="bi bi-calendar2"></i>&nbsp;&nbsp;Timeline</button>
            <button type="button" class="btn btn-sm tab-btn"><i class="bi bi-graph-up"></i>&nbsp;&nbsp;Analytics</button>
        </div>
        <button type="button" class="btn new-btn btn-sm ms-3">+ New Engagement</button>
    </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>