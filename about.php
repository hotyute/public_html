<?php include 'header.php'; ?>
<div class="main-container">
    <main>
        <section>
            <h2>About Us</h2>
            <p id="intro-text">Welcome to our community! We are dedicated to spreading and understanding the Word of God. Our journey began many years ago, rooted in a passion for faith and a desire to bring people closer to the divine.</p>
            <div id="more-info" style="display: none;">
                <h3>Our Mission</h3>
                <p>Our mission is to create a space where everyone feels welcome and valued. We believe in the power of community, and our goal is to provide a nurturing environment for spiritual growth, learning, and fellowship. Through our services, outreach programs, and events, we aim to inspire and support each other in our walk of faith.</p>

                <h3>Our History</h3>
                <p>Founded in [Year], our community has grown from a small group of like-minded individuals to a vibrant congregation of believers. Over the years, we have expanded our reach through various ministries and initiatives, always staying true to our core values of love, respect, and compassion.</p>

                <h3>What We Offer</h3>
                <ul>
                    <li><strong>Weekly Services:</strong> Join us every [Day] for our uplifting and engaging articles and insights into Biblical teachings and events.</li>
                    <li><strong>Bible Studies:</strong> Deepen your understanding of the scriptures with our regular Bible study sessions.</li>
                    <li><strong>Community Outreach:</strong> We are committed to serving the global community through various outreach programs.</li>
                </ul>

                <h3>Meet Our Team</h3>
                <p>Our dedicated team is here to guide, support, and inspire you. Meet our leadership:</p>
                <ul id="team-list">
                    <li><strong>Samuel Mason Jr</strong> - Project Leader</li>
                    <li><strong>CaribbeanSkies</strong> - Lead Editor</li>
                </ul>

                <h3>Testimonials</h3>
                <div class="testimonial-slider">
                    <div class="testimonial">
                        <p>"This community has truly changed my life. The love and support I've received here are incredible."</p>
                        <p>- Sarah K.</p>
                    </div>
                    <div class="testimonial">
                        <p>"A wonderful place to grow in faith and connect with others who share your beliefs."</p>
                        <p>- Michael B.</p>
                    </div>
                    <div class="testimonial">
                        <p>"The Bible studies are enlightening, and the services are always uplifting. I'm so glad I found this community."</p>
                        <p>- Laura T.</p>
                    </div>
                </div>
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
        const testimonials = document.querySelectorAll('.testimonial');
        let currentTestimonial = 0;

        toggleButton.addEventListener('click', function() {
            if (moreInfo.style.display === 'none') {
                moreInfo.style.display = 'block';
                toggleButton.textContent = 'Show Less';
                introText.classList.add('fade-out');
                moreInfo.classList.add('fade-in');
                startTestimonialSlider();
            } else {
                moreInfo.style.display = 'none';
                toggleButton.textContent = 'Learn More';
                introText.classList.remove('fade-out');
            }
        });

        function startTestimonialSlider() {
            testimonials.forEach((testimonial, index) => {
                testimonial.style.display = index === currentTestimonial ? 'block' : 'none';
            });

            setInterval(() => {
                testimonials[currentTestimonial].style.display = 'none';
                currentTestimonial = (currentTestimonial + 1) % testimonials.length;
                testimonials[currentTestimonial].style.display = 'block';
            }, 5000); // Change testimonial every 5 seconds
        }

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
        .testimonial { display: none; animation: fadeIn 1s forwards; }
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

    .testimonial-slider {
        margin-top: 20px;
        border-top: 1px solid #ddd;
        padding-top: 20px;
    }

    .testimonial {
        text-align: center;
        font-style: italic;
        color: #555;
    }

    #team-list {
        list-style-type: none;
        padding: 0;
    }

    #team-list li {
        padding: 5px 0;
    }

    ul {
        padding-left: 20px;
    }

    h3 {
        margin-top: 30px;
    }
</style>
