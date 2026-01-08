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
    .btn-new-engagement {
      background-color: #0d6efd;
      color: #fff;
      font-weight: 500;
    }
    .btn-new-engagement:hover {
      background-color: #0b5ed7;
      color: #fff;
    }
    .header-actions .btn {
      margin-left: 8px;
    }
    /* Container for the tabs */
    .custom-tabs {
      display: flex;
      gap: 1rem;
      border-bottom: 2px solid #e0e0e0;
      margin-bottom: 1rem;
    }

    /* Each tab */
    .custom-tab {
      padding: 0.5rem 1rem;
      cursor: pointer;
      border-radius: 0.5rem 0.5rem 0 0;
      transition: all 0.2s;
      font-weight: 500;
    }

    /* Active tab */
    .custom-tab.active {
      background-color: #0d6efd; /* Bootstrap primary color */
      color: white;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    /* Hover effect */
    .custom-tab:hover {
      background-color: #e7f1ff;
    }
  </style>
</head>
<body>

    <div class="header-container">
    <div>
      <div class="header-title">Engagement Tracker</div>
      <div class="header-subtitle">Manage your audit engagements</div>
    </div>
    <div class="header-actions d-flex align-items-center">
      <button type="button" class="btn btn-outline-secondary btn-sm">Board</button>
      <button type="button" class="btn btn-outline-secondary btn-sm">List</button>
      <button type="button" class="btn btn-outline-secondary btn-sm">Timeline</button>
      <button type="button" class="btn btn-outline-secondary btn-sm">Analytics</button>
      <button type="button" class="btn btn-new-engagement btn-sm ms-3">+ New Engagement</button>
    </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>