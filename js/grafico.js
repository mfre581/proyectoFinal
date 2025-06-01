document.addEventListener("DOMContentLoaded", () => {
  // Obtén el contexto del canvas
  const ctx = document.getElementById("graficoVotos").getContext("2d");

  // Los datos 'labels' y 'votos' los pasaremos desde PHP a JS en el HTML
  // Por eso, asumimos que las variables 'labels' y 'votos' ya existen en el scope

  const graficoVotos = new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels, // variables definidas en el HTML
      datasets: [
        {
          label: "Votos por foto",
          data: votos,
          backgroundColor: "rgba(54, 162, 235, 0.5)",
          borderColor: "rgba(54, 162, 235, 1)",
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1,
          },
        },
      },
    },
  });
});
