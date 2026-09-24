(() => {
  const wrapper = document.getElementById('appWrapper');
  const toggle = document.getElementById('sidebarToggle');
  const backdrop = document.getElementById('sidebarBackdrop');

  const closeSidebar = () => wrapper?.classList.remove('sidebar-open');
  const toggleSidebar = () => wrapper?.classList.toggle('sidebar-open');

  toggle?.addEventListener('click', toggleSidebar);
  backdrop?.addEventListener('click', closeSidebar);

  // DataTables
  if (window.jQuery && $.fn.DataTable) {
    $('.datatable').each(function () {
      const $table = $(this);
      if ($.fn.DataTable.isDataTable($table)) return;
      $table.DataTable({
        pageLength: 10,
        order: [],
        language: {
          search: 'ค้นหา:',
          lengthMenu: 'แสดง _MENU_ รายการ',
          info: 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
          infoEmpty: 'ไม่มีข้อมูล',
          infoFiltered: '(กรองจากทั้งหมด _MAX_ รายการ)',
          zeroRecords: 'ไม่พบข้อมูลที่ค้นหา',
          paginate: {
            first: 'แรก',
            last: 'สุดท้าย',
            next: 'ถัดไป',
            previous: 'ก่อนหน้า'
          }
        }
      });
    });
  }

  // SweetAlert flash
  const flash = document.getElementById('flash-data');
  if (flash && window.Swal) {
    const type = flash.dataset.type || 'info';
    const iconMap = {
      success: 'success',
      error: 'error',
      warning: 'warning',
      info: 'info',
      danger: 'error'
    };
    Swal.fire({
      icon: iconMap[type] || 'info',
      title: flash.dataset.message,
      timer: 2600,
      showConfirmButton: false,
      toast: true,
      position: 'top-end'
    });
  }

  // Confirm links / forms
  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      const msg = el.getAttribute('data-confirm') || 'ยืนยันการทำรายการ?';
      const href = el.getAttribute('href');
      const form = el.closest('form');

      Swal.fire({
        title: msg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#1f4e79',
        cancelButtonColor: '#6c757d'
      }).then((result) => {
        if (!result.isConfirmed) return;
        if (href) {
          window.location.href = href;
        } else if (form) {
          form.submit();
        }
      });
    });
  });

  document.querySelectorAll('form[data-confirm-submit]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      if (form.dataset.confirmed === '1') return;
      e.preventDefault();
      Swal.fire({
        title: form.getAttribute('data-confirm-submit') || 'ยืนยันการบันทึก?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#e07a2f'
      }).then((result) => {
        if (result.isConfirmed) {
          form.dataset.confirmed = '1';
          form.submit();
        }
      });
    });
  });
})();
