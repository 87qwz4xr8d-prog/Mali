document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      const message = el.getAttribute('data-confirm') || 'ยืนยันการดำเนินการ?';
      const href = el.getAttribute('href');
      const formId = el.getAttribute('data-form');

      Swal.fire({
        title: 'ยืนยัน',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1f7a4d',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก',
      }).then((result) => {
        if (!result.isConfirmed) return;
        if (formId) {
          document.getElementById(formId)?.submit();
        } else if (href) {
          window.location.href = href;
        }
      });
    });
  });

  const flash = document.getElementById('flash-data');
  if (flash) {
    const type = flash.dataset.type || 'info';
    const message = flash.dataset.message || '';
    const iconMap = { success: 'success', danger: 'error', warning: 'warning', info: 'info' };
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: iconMap[type] || 'info',
      title: message,
      showConfirmButton: false,
      timer: 3200,
      timerProgressBar: true,
    });
  }
});
