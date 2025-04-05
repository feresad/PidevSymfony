/**
 * Retrieves the user ID from localStorage
 * @returns {string|null} The user ID if it exists, null otherwise
 */
function getUserID() {
    try {
        const userId = localStorage.getItem('userId');
        return userId || null;
    } catch (error) {
        console.error('Error accessing localStorage:', error);
        return null;
    }
}

/**
 * Retrieves the user email from localStorage
 * @returns {string|null} The user email if it exists, null otherwise
 */
function getUserEmail() {
    try {
        const userEmail = localStorage.getItem('userEmail');
        return userEmail || null;
    } catch (error) {
        console.error('Error accessing localStorage:', error);
        return null;
    }
} 