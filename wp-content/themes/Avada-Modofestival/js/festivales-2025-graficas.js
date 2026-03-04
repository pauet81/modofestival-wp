(function () {

  function initGraficasFestivales() {

    const rankingCanvas = document.getElementById('rankingFestivales');
    const comunidadesCanvas = document.getElementById('comunidadesFestivales');

    if (!rankingCanvas || !comunidadesCanvas || typeof Chart === 'undefined') {
      return false;
    }

    /* =========================
       1. GRÁFICO FESTIVALES
       ========================= */

    new Chart(rankingCanvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels: [
          'Arenal Sound',
          'Primavera Sound',
          'Viña Rock',
          'Rototom Sunsplash',
          'Sonorama Ribera',
          'Medusa Sunbeach Festival',
          'Sónar',
          'Mad Cool',
          'Resurrection Fest',
          'Dreambeach',
          'FIB Benicàssim',
          'Zevra Festival',
          'O Son do Camiño',
          'Bilbao BBK Live'
        ],
        datasets: [{
          label: 'Asistencia',
          data: [
            300000, 293000, 240000, 218000, 200000, 185000,
            161000, 150000, 145000, 140000, 135000, 130000,
            125000, 115000
          ],
          backgroundColor: [
            '#4A90E2', '#4A90E2', '#4A90E2', '#4A90E2', '#BFD9F2',
            '#BFD9F2', '#4A90E2', '#BFD9F2', '#4A90E2', '#BFD9F2',
            '#4A90E2', '#BFD9F2', '#BFD9F2', '#4A90E2'
          ],
          borderRadius: 4,
          barPercentage: 0.7,
          categoryPercentage: 0.8
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                return ctx.raw.toLocaleString('es-ES') + ' asistentes';
              }
            }
          }
        },
        scales: {
          x: {
            ticks: {
              callback: v => v.toLocaleString('es-ES')
            }
          }
        }
      }
    });

   /* =========================
   2. GRÁFICO COMUNIDADES
   ========================= */

const isMobile = window.innerWidth < 768;

new Chart(comunidadesCanvas.getContext('2d'), {
  type: 'bar',
  data: {
    labels: [
      'Comunidad Valenciana',
      'Cataluña',
      'Castilla-La Mancha',
      'Madrid',
      'Galicia',
      'País Vasco'
    ],
    datasets: [{
      data: [650000, 536000, 240000, 150000, 125000, 115000],
      backgroundColor: '#4A90E2',
      borderRadius: 4,
      barPercentage: 0.6
    }]
  },
  options: {
    indexAxis: isMobile ? 'y' : 'x',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => ctx.raw.toLocaleString('es-ES') + ' asistentes'
        }
      }
    },
    scales: isMobile
      ? {
          y: {
            ticks: {
              autoSkip: false
            }
          },
          x: {
            ticks: {
              callback: v => v.toLocaleString('es-ES')
            }
          }
        }
      : {
          x: {
            ticks: {
              autoSkip: false
            }
          },
          y: {
            ticks: {
              callback: v => v.toLocaleString('es-ES')
            }
          }
        }
  }
});

    return true;
  }

  /* =========================
     INTENTO PROGRESIVO
     ========================= */

  let tries = 0;
  const maxTries = 20;

  const interval = setInterval(() => {
    if (initGraficasFestivales() || tries > maxTries) {
      clearInterval(interval);
    }
    tries++;
  }, 300);

})();



