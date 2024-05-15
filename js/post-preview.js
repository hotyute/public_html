console.log('JavaScript loaded.');

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