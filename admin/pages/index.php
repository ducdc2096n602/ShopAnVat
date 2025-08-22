
<?php
    require_once('../../helpers/startSession.php');
      startRoleSession('admin');
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="stats-grid">
  <div class="stat-card bg-blue">
    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
    <div class="stat-label">Tổng đơn hàng</div>
    <div class="stat-value" id="totalOrders">0</div>
  </div>

  <div class="stat-card bg-orange">
    <div class="stat-icon"><i class="fas fa-clock"></i></div>
    <div class="stat-label">Đơn chờ xác nhận</div>
    <div class="stat-value" id="pendingOrders">0</div>
  </div>

  <div class="stat-card bg-green">
    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
    <div class="stat-label">Doanh thu hôm nay</div>
    <div class="stat-value" id="revenueToday">0 ₫</div>
  </div>

  <div class="stat-card bg-purple">
    <div class="stat-icon"><i class="fas fa-users"></i></div>
    <div class="stat-label">Khách hàng</div>
    <div class="stat-value" id="customerCount">0</div>
  </div>
</div>

<div style="width: 90%; margin: 40px auto;">
  <h3>Biểu đồ thống kê doanh thu theo thời gian</h3>
  <div style="margin: 15px 0;">
    <label>Từ: <input type="date" id="fromDate"></label>
    <label>Đến: <input type="date" id="toDate"></label>
    <label>Loại: 
      <select id="filterType" onchange="changeDateType()">
        <option value="day">Theo ngày</option>
        <option value="month">Theo tháng</option>
        <option value="year">Theo năm</option>
      </select>
    </label>
    <button onclick="loadRevenueByTime(true)">Xem biểu đồ</button>
  </div>
  <canvas id="revenueTimeChart" height="100"></canvas>

  <h3 style="margin-top: 50px;">Biểu đồ thống kê số đơn hàng theo thời gian</h3>
  <div style="margin: 15px 0;">
    <label>Từ: <input type="date" id="orderFrom"></label>
    <label>Đến: <input type="date" id="orderTo"></label>
    <label>Loại: 
      <select id="orderFilterType" onchange="changeOrderDateType()">
        <option value="day">Theo ngày</option>
        <option value="month">Theo tháng</option>
        <option value="year">Theo năm</option>
      </select>
    </label>
    <button onclick="loadOrderChart(true)">Xem biểu đồ</button>
  </div>
  <canvas id="orderTimeChart" height="100"></canvas>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

 <style>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin: 20px auto;
  padding: 0 20px;
  max-width: 1200px;
}

.stat-card {
  background-color: #fff;
  padding: 16px 20px;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  color: #fff;
  min-height: 120px;
  transition: transform 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-icon {
  font-size: 22px;
  margin-bottom: 10px;
  opacity: 0.85;
}

.stat-label {
  font-size: 0.95rem;
  font-weight: 500;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 1.3rem;
  font-weight: bold;
}

.bg-blue    { background: linear-gradient(45deg, #2196f3, #0d47a1); }
.bg-orange  { background: linear-gradient(45deg, #ff9800, #e65100); }
.bg-green   { background: linear-gradient(45deg, #4caf50, #1b5e20); }
.bg-purple  { background: linear-gradient(45deg, #9c27b0, #4a148c); }

@media screen and (max-width: 992px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media screen and (max-width: 576px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
<script>
fetch('statistic/overview_stats.php')
  .then(res => res.json())
  .then(data => {
    document.getElementById('totalOrders').textContent = data.total_orders;
    document.getElementById('pendingOrders').textContent = data.pending_orders;
    document.getElementById('revenueToday').textContent = 
      (data.revenue_today ? data.revenue_today.toLocaleString('vi-VN') + ' ₫' : '0 ₫');
    document.getElementById('customerCount').textContent = data.customer_count;
  })
  .catch(err => console.error('Lỗi thống kê tổng quan:', err));

function changeDateType() {
  const type = document.getElementById('filterType').value;
  const from = document.getElementById('fromDate');
  const to = document.getElementById('toDate');

  switch (type) {
    case 'month':
      from.type = to.type = 'month'; break;
    case 'year':
      from.type = to.type = 'number';
      from.min = to.min = 2000;
      from.max = to.max = new Date().getFullYear(); break;
    default:
      from.type = to.type = 'date'; break;
  }
}

function changeOrderDateType() {
  const type = document.getElementById('orderFilterType').value;
  const from = document.getElementById('orderFrom');
  const to = document.getElementById('orderTo');

  switch (type) {
    case 'month':
      from.type = to.type = 'month'; break;
    case 'year':
      from.type = to.type = 'number';
      from.min = to.min = 2000;
      from.max = to.max = new Date().getFullYear(); break;
    default:
      from.type = to.type = 'date'; break;
  }
}

let revenueChart;
function loadRevenueByTime(isUserTriggered = false) {
  const from = document.getElementById('fromDate').value;
  const to = document.getElementById('toDate').value;
  const type = document.getElementById('filterType').value;

  if (!from || !to) {
  if (isUserTriggered)
    Swal.fire('Thiếu thông tin!', 'Vui lòng chọn đủ ngày bắt đầu và kết thúc.', 'warning');
  return;
}


  if (new Date(from) > new Date(to)) {
  if (isUserTriggered) {
    Swal.fire({
      icon: 'warning',
      title: 'Dữ liệu nhập không hợp lệ',
      text: 'Ngày bắt đầu không được lớn hơn ngày kết thúc.',
    });
  }
  return;
}


  fetch(`statistic/get_revenue_by_time.php?from=${from}&to=${to}&type=${type}`)
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        if (isUserTriggered) alert(data.error);
        return;
      }

      const noData = !data.labels || data.labels.length === 0 || 
                     !data.revenues || data.revenues.every(val => val == 0);

      if (noData && isUserTriggered) {
  Swal.fire({
    icon: 'info',
    title: 'Thông báo',
    text: 'Không có dữ liệu doanh thu trong khoảng thời gian này.',
    confirmButtonText: 'Đóng'
  });
}


      const ctx = document.getElementById('revenueTimeChart').getContext('2d');
      if (revenueChart) revenueChart.destroy();
      revenueChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: data.labels || [],
          datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: data.revenues || [],
            backgroundColor: 'rgba(75, 192, 192, 0.6)'
          }]
        },
        options: {
          responsive: true,
          plugins: {
            title: {
              display: true,
              text: 'Doanh thu theo ' + (type === 'day' ? 'ngày' : type === 'month' ? 'tháng' : 'năm')
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: val => val.toLocaleString('vi-VN') + ' ₫'
              }
            }
          }
        }
      });
    })
    .catch(err => console.error('Lỗi khi tải biểu đồ doanh thu:', err));
}




let orderChart;
function loadOrderChart(isUserTriggered = false) {
  const from = document.getElementById('orderFrom').value;
  const to = document.getElementById('orderTo').value;
  const type = document.getElementById('orderFilterType').value;

  if (!from || !to) {
    if (isUserTriggered) alert("Vui lòng chọn đủ ngày bắt đầu và kết thúc");
    return;
  }

  if (new Date(from) > new Date(to)) {
  if (isUserTriggered) {
    Swal.fire({
      icon: 'warning',
      title: 'Dữ liệu nhập không hợp lệ',
      text: 'Ngày bắt đầu không được lớn hơn ngày kết thúc.',
    });
  }
  return;
}


  fetch(`statistic/get_orders_by_time.php?from=${from}&to=${to}&type=${type}`)
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        if (isUserTriggered) alert(data.error);
        return;
      }

      const noData = !data.labels || data.labels.length === 0 || 
                     !data.orders || data.orders.every(val => val == 0);

      if (noData && isUserTriggered) {
  Swal.fire({
    icon: 'info',
    title: 'Thông báo',
    text: 'Không có dữ liệu đơn hàng trong khoảng thời gian này.',
    confirmButtonText: 'Đóng'
  });
}


      const ctx = document.getElementById('orderTimeChart').getContext('2d');
      if (orderChart) orderChart.destroy();
      orderChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: data.labels || [],
          datasets: [{
            label: 'Số đơn hàng',
            data: data.orders || [],
            borderColor: 'rgba(255, 159, 64, 1)',
            backgroundColor: 'rgba(255, 159, 64, 0.3)',
            fill: true,
            tension: 0.3
          }]
        },
        options: {
          responsive: true,
          plugins: {
            title: {
              display: true,
              text: 'Số đơn hàng theo ' + (type === 'day' ? 'ngày' : type === 'month' ? 'tháng' : 'năm')
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              precision: 0
            }
          }
        }
      });
    })
    .catch(err => console.error('Lỗi khi load biểu đồ đơn hàng:', err));
}




document.addEventListener('DOMContentLoaded', () => {
  const today = new Date();
  const oneMonthAgo = new Date(today);
  oneMonthAgo.setMonth(today.getMonth() - 1);

  const formatDate = (date) => {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  };

  const fromDateStr = formatDate(oneMonthAgo);
  const toDateStr = formatDate(today);

  // Biểu đồ DOANH THU
  const revenueFrom = document.getElementById('fromDate');
  const revenueTo = document.getElementById('toDate');
  const revenueType = document.getElementById('filterType');

  if (revenueFrom && revenueTo && revenueType) {
    revenueFrom.type = 'date';
    revenueTo.type = 'date';
    revenueFrom.value = fromDateStr;
    revenueTo.value = toDateStr;
    revenueType.value = 'day'; 

    loadRevenueByTime(); // Tự động gọi biểu đồ doanh thu
  }

  // === Biểu đồ ĐƠN HÀNG ===
  const orderFrom = document.getElementById('orderFrom');
  const orderTo = document.getElementById('orderTo');
  const orderType = document.getElementById('orderFilterType');

  if (orderFrom && orderTo && orderType) {
    orderFrom.type = 'date';
    orderTo.type = 'date';
    orderFrom.value = fromDateStr;
    orderTo.value = toDateStr;
    orderType.value = 'day'; 

    loadOrderChart(); // Tự động gọi biểu đồ đơn hàng
  }
});


</script>
<script>
fetch('statistic/overview_stats.php')
  .then(res => res.json())
  .then(data => {
    document.getElementById('totalOrders').textContent = data.total_orders;
    document.getElementById('pendingOrders').textContent = data.pending_orders;
    
    // Xử lý và định dạng doanh thu hôm nay
    let revenueTodayValue = parseFloat(data.revenue_today); // Chuyển sang số thực
    if (isNaN(revenueTodayValue)) { // Kiểm tra nếu không phải số hợp lệ
        revenueTodayValue = 0;
    }
    document.getElementById('revenueToday').textContent = 
      revenueTodayValue.toLocaleString('vi-VN', { 
          minimumFractionDigits: 0, 
          maximumFractionDigits: 0 
      }) + ' ₫'; // Thêm ký tự tiền tệ
    
    document.getElementById('customerCount').textContent = data.customer_count;
  })
  .catch(err => console.error('Lỗi thống kê tổng quan:', err));


</script>
<?php include '../includes/footer.php'; ?>
