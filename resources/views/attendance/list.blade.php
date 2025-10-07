<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Attendance History</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', sans-serif; background: #f9fafb; color: #333; }
    .container { margin: 20px; background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06); overflow: hidden; }
    .header { background: linear-gradient(135deg, #d0eefd, #f6dfff); padding: 15px; display: flex; justify-content: center; align-items: center; }
    .header h2 { margin: 0; font-size: 26px; color: #1e293b; }
    .header a { padding: 10px 14px; border: 2px solid #1e40af; border-radius: 6px; text-decoration: none; color: #1e40af; font-weight: 600; transition: all 0.2s ease; }
    .header a:hover { background: #1e40af; color: #fff; }

    .transaction-table {
      width: 100%;
      border: 1px solid #ddd;
      margin: 20px 0;
      text-align: center;
      border-collapse: collapse;
    }

    .transaction-table th, .transaction-table td {
      padding: 10px;
      border: 1px solid #ddd;
    }

    .copy-btn {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 1rem;
      margin-left: auto;
    }

    .cell-flex {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 8px;
    }

    p.no-data {
      padding: 20px;
      text-align: center;
    }

    /* Custom pagination styles */
    .pagination {
      text-align: center;
      margin: 20px auto;
    }

    .pagination a, .pagination span {
      display: inline-block;
      margin: 0 5px;
      padding: 6px 10px;
      text-decoration: none;
      color: rebeccapurple;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-weight: 500;
    }

    .pagination a:hover {
      background-color: #eee;
    }

    .pagination .current {
      background-color: #f3e9ff;
      border-color: rebeccapurple;
      font-weight: bold;
      text-decoration: underline;
    }

    .pagination .arrow {
      font-size: 1.8rem;
      color: rebeccapurple;
      margin: 0 10px;
    }

    .pagination .arrow.disabled {
      color: gray;
      pointer-events: none;
    }
  </style>
</head>
<body>
<div class="container">
  <div class="header">
    <h2>Attendance History</h2>
  </div>

@if($list->count())
  <div style="display: flex; flex-direction: column; align-items: center;">
    <table class="transaction-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Department</th>
          <th>Division</th>
          <th>Date Time</th>
        </tr>
      </thead>
      <tbody>
        @foreach($list as $item)
        <tr>
          <td>{{ $item->user->employee_id }}</td>
          <td>{{ $item->user->name }}</td>
          <td>{{ $item->user->department->name }}</td>
          <td>{{ $item->user->department->division->name }}</td>
          <td>{{ $item->datetime }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    {{-- Custom Pagination --}}
    @if ($list->hasPages())
      <div class="pagination">
        {{-- Previous --}}
        @if ($list->onFirstPage())
          <span class="arrow disabled">&#10094;</span>
        @else
          <a href="{{ $list->previousPageUrl() }}" class="arrow">&#10094;</a>
        @endif

        {{-- Page Numbers --}}
        @foreach ($list->links()->elements[0] as $page => $url)
          @if ($page == $list->currentPage())
            <span class="current">{{ $page }}</span>
          @else
            <a href="{{ $url }}">{{ $page }}</a>
          @endif
        @endforeach

        {{-- Next --}}
        @if ($list->hasMorePages())
          <a href="{{ $list->nextPageUrl() }}" class="arrow">&#10095;</a>
        @else
          <span class="arrow disabled">&#10095;</span>
        @endif
      </div>
    @endif
  </div>
@else
  <p class="no-data">No transactions found.</p>
@endif

</div>

<script>
  function fallbackCopyText(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    try {
      const success = document.execCommand('copy');
      document.body.removeChild(textarea);
      return success;
    } catch (err) {
      document.body.removeChild(textarea);
      return false;
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.copy-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const fullAddress = btn.getAttribute('data-address');
        btn.disabled = true;
        const useClipboard = navigator.clipboard && typeof navigator.clipboard.writeText === 'function';

        const copyPromise = useClipboard
          ? navigator.clipboard.writeText(fullAddress)
          : Promise.resolve(fallbackCopyText(fullAddress));

        copyPromise
          .then(result => {
            const success = useClipboard ? true : result;
            btn.textContent = success ? '✓' : '✗';
          })
          .catch(() => {
            btn.textContent = '✗';
          })
          .finally(() => {
            setTimeout(() => {
              btn.textContent = '📋';
              btn.disabled = false;
            }, 1500);
          });
      });
    });
  });
</script>
</body>
</html>
