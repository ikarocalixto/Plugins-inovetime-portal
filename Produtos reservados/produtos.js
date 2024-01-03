document.addEventListener("DOMContentLoaded", function() {
    const toggleButton = document.getElementById("myplugin-toggle-reserved-products");
    const container = document.getElementById("myplugin-reserved-products-container");

    toggleButton.addEventListener("click", function() {
        if (container.style.display === "none" || container.style.display === "") {
            container.style.display = "block";
        } else {
            container.style.display = "none";
        }
    });
});



