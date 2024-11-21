// post-preview.js

function getCharacterCount(width, height) {
    // Define ranges for character count adjustment
    const minWidth = 320;
    const maxWidth = 1200;
    const minHeight = 480;
    const maxHeight = 800;

    const minCharsWidth = 30; // Minimum characters at minimum width
    const maxCharsWidth = 75; // Maximum characters at maximum width
    const minCharsHeight = 30; // Minimum characters at minimum height
    const maxCharsHeight = 75; // Maximum characters at maximum height

    // Linear scaling calculations
    const slopeWidth = (maxCharsWidth - minCharsWidth) / (maxWidth - minWidth);
    const slopeHeight = (maxCharsHeight - minCharsHeight) / (maxHeight - minHeight);

    const charLimitWidth = width <= minWidth ? minCharsWidth :
        width >= maxWidth ? maxCharsWidth :
        Math.floor(minCharsWidth + slopeWidth * (width - minWidth));

    const charLimitHeight = height <= minHeight ? minCharsHeight :
        height >= maxHeight ? maxCharsHeight :
        Math.floor(minCharsHeight + slopeHeight * (height - minHeight));

    // Use the smaller of the two calculated character limits
    return Math.min(charLimitWidth, charLimitHeight);
}

function adjustFontSize(width, height) {
    // Base font size
    const baseFontSize = 14; // Base font size in pixels

    // Scaling factors
    const widthFactor = 0.01;
    const heightFactor = 0.015;

    // Calculate font size based on width and height
    const fontSizeWidth = baseFontSize + (width - 320) * widthFactor;
    const fontSizeHeight = baseFontSize + (height - 480) * heightFactor;

    // Use the smaller of the two to adjust font size
    return Math.min(fontSizeWidth, fontSizeHeight);
}

function adjustContentPreview() {
    const previews = document.querySelectorAll('.content-preview');
    const screenWidth = window.innerWidth;
    const screenHeight = window.innerHeight;
    const charLimit = getCharacterCount(screenWidth, screenHeight);
    const fontSize = adjustFontSize(screenWidth, screenHeight);

    previews.forEach(preview => {
        const fullText = preview.getAttribute('data-content');
        preview.textContent = fullText.length > charLimit ? fullText.substring(0, charLimit) + '...' : fullText;
        preview.style.fontSize = `${fontSize}px`; // Apply the dynamically calculated font size
    });
}

// Adjust content on load and resize
window.addEventListener('load', adjustContentPreview);
window.addEventListener('resize', adjustContentPreview);

// Slider Navigation Functionality
document.addEventListener('DOMContentLoaded', function() {
    const sliderTrack = document.querySelector('.slider-track');
    const prevButton = document.querySelector('.prev-button');
    const nextButton = document.querySelector('.next-button');
    const posts = document.querySelectorAll('.post-preview');
    const slider = document.querySelector('.slider');

    if (!sliderTrack || !prevButton || !nextButton || posts.length === 0) return;

    let postWidth = posts[0].clientWidth + 20; // Width + margin (10px each side)
    let currentIndex = 0;
    let postsToShow = Math.floor(slider.clientWidth / postWidth);
    let maxIndex = posts.length - postsToShow;

    // Initialize global indices for access in other functions
    window.currentIndex = currentIndex;
    window.maxIndex = maxIndex >= 0 ? maxIndex : 0;

    // Handle window resize to recalculate postWidth, postsToShow, and maxIndex
    window.addEventListener('resize', () => {
        postWidth = posts[0].clientWidth + 20;
        postsToShow = Math.floor(slider.clientWidth / postWidth);
        maxIndex = posts.length - postsToShow;
        window.maxIndex = maxIndex >= 0 ? maxIndex : 0;

        // Adjust currentIndex if it exceeds maxIndex
        if (window.currentIndex > window.maxIndex) {
            window.currentIndex = window.maxIndex;
            sliderTrack.style.transform = `translateX(-${window.currentIndex * postWidth}px)`;
        }

        updateSliderButtonStates();
    });

    // Next Button Click Event
    nextButton.addEventListener('click', () => {
        if (window.currentIndex < window.maxIndex) {
            window.currentIndex++;
            sliderTrack.style.transform = `translateX(-${window.currentIndex * postWidth}px)`;
            updateSliderButtonStates();
        }
    });

    // Previous Button Click Event
    prevButton.addEventListener('click', () => {
        if (window.currentIndex > 0) {
            window.currentIndex--;
            sliderTrack.style.transform = `translateX(-${window.currentIndex * postWidth}px)`;
            updateSliderButtonStates();
        }
    });

    // Function to update slider button states (enable/disable)
    function updateSliderButtonStates() {
        prevButton.disabled = window.currentIndex === 0;
        nextButton.disabled = window.currentIndex >= window.maxIndex;

        // Update button styles
        prevButton.style.opacity = window.currentIndex === 0 ? '0.5' : '1';
        nextButton.style.opacity = window.currentIndex >= window.maxIndex ? '0.5' : '1';
        prevButton.style.cursor = window.currentIndex === 0 ? 'not-allowed' : 'pointer';
        nextButton.style.cursor = window.currentIndex >= window.maxIndex ? 'not-allowed' : 'pointer';
    }

    // Initial button state setup
    updateSliderButtonStates();
});
