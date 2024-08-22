<?php include 'header.php'; ?>
<div class="main-container">
    <main>
        <section>
            <h2>About Us</h2>
            <p id="intro-text">This is the about us page. We are a community devoted to spreading and understanding the Word of God.</p>
            <div id="more-info" style="display: none;">
                <p>Our mission is to bring people closer to faith through fellowship, study, and worship. We believe in the power of unity and compassion, and we strive to make a positive impact in our community and beyond.</p>
                <p>Join us in our journey of spiritual growth and discovery. Whether you're new to the faith or have been walking this path for years, there's a place for you here.</p>
            </div>
            <button id="toggle-info" class="cool-button">Learn More</button>
        </section>
    </main>
</div>
<?php include 'footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const introText = document.getElementById('intro-text');
        const moreInfo = document.getElementById('more-info');
        const toggleButton = document.getElementById('toggle-info');

        toggleButton.addEventListener('click', function() {
            if (moreInfo.style.display === 'none') {
                moreInfo.style.display = 'block';
                toggleButton.textContent = 'Show Less';
                introText.classList.add('fade-out');
                moreInfo.classList.add('fade-in');
            } else {
                moreInfo.style.display = 'none';
                toggleButton.textContent = 'Learn More';
                introText.classList.remove('fade-out');
            }
        });

        // Adding some smooth scrolling for a cool effect
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();

                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    });

    // Simple fade in/out animations
    const fadeOutAnimation = `
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    `;
    const fadeInAnimation = `
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    `;
    const style = document.createElement('style');
    style.textContent = `
        .fade-out { animation: fadeOut 1s forwards; }
        .fade-in { animation: fadeIn 1s forwards; }
        ${fadeOutAnimation}
        ${fadeInAnimation}
    `;
    document.head.appendChild(style);
</script>

<style>
    .cool-button {
        background-color: #4CAF50;
        color: white;
        padding: 10px 20px;
        border: none;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s ease;
    }

    .cool-button:hover {
        background-color: #45a049;
    }

    .fade-in {
        animation: fadeIn 1s forwards;
    }

    .fade-out {
        animation: fadeOut 1s forwards;
    }
</style>
