document.addEventListener("DOMContentLoaded", function () {
  function updateTimers() {
    document.querySelectorAll(".timer").forEach((timer) => {
      let timeLeft = parseInt(timer.getAttribute("data-time"));

      if (timeLeft > 0) {
        let hours = Math.floor(timeLeft / 3600);
        let minutes = Math.floor((timeLeft % 3600) / 60);
        let seconds = timeLeft % 60;

        timer.innerText = `${hours}h ${minutes}m ${seconds}s`;
        timer.setAttribute("data-time", timeLeft - 1);
      } else {
        timer.innerText = "Auction Ended";
      }
    });
  }

  setInterval(updateTimers, 1000);
  updateTimers(); // Run immediately on page load
});
