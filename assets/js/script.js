$(document).ready(function() {
    if($('#salesChart').length) {
        $.ajax({
            url: '../api/get-sales-data.php',
            method: 'GET',
            success: function(response) {
                const ctx = document.getElementById('salesChart').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: response.labels,
                        datasets: [{
                            label: 'Penjualan (Rp)',
                            data: response.data,
                            backgroundColor: '#4e73df',
                            borderColor: '#2e59d9',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp' + value.toLocaleString('id-ID');
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Rp' + context.raw.toLocaleString('id-ID');
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    }
    
    $('.delete-btn').on('click', function(e) {
        if(!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            e.preventDefault();
        }
    });
    
    $('#sidebarToggle').on('click', function() {
        $('body').toggleClass('sidebar-toggled');
        $('.sidebar').toggleClass('toggled');
    });
});


$(function(){
  $('.delete-btn').click(function(){
    return confirm('Yakin ingin menghapus?');
  });
  // placeholder: AJAX untuk Chart.js jika perlu
});
$(function(){
  $('.delete-btn').click(function(){
    return confirm('Yakin ingin menghapus?');
  });
  // Tambah AJAX atau Chart.js di sini
});

