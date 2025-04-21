/**
 * Test Progress Bar Utility
 * This utility provides functions to create and update progress bars for tests
 */

class TestProgress {
    /**
     * Initialize a simple progress bar
     * @param {string} containerId - ID of the container element
     * @param {boolean} showPercentage - Whether to show percentage text inside the bar
     * @returns {object} - API for updating the progress
     */
    static initSimpleProgressBar(containerId, showPercentage = true) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`Container with ID ${containerId} not found`);
            return null;
        }

        // Create progress container
        const progressContainer = document.createElement('div');
        progressContainer.className = 'test-progress-container';
        
        // Create progress bar
        const progressBar = document.createElement('div');
        progressBar.className = 'test-progress-bar';
        
        // Add text element if needed
        let progressText = null;
        if (showPercentage) {
            progressText = document.createElement('div');
            progressText.className = 'test-progress-text';
            progressText.textContent = '0%';
            progressBar.appendChild(progressText);
        }
        
        // Add to DOM
        progressContainer.appendChild(progressBar);
        container.appendChild(progressContainer);
        
        // Return API
        return {
            /**
             * Update the progress bar
             * @param {number} progress - Progress value from 0 to 100
             */
            updateProgress: (progress) => {
                const clampedProgress = Math.max(0, Math.min(100, progress));
                progressBar.style.width = `${clampedProgress}%`;
                
                if (progressText) {
                    progressText.textContent = `${Math.round(clampedProgress)}%`;
                }
            },
            
            /**
             * Show or hide the progress bar
             * @param {boolean} visible - Whether the progress bar should be visible
             */
            setVisible: (visible) => {
                progressContainer.style.display = visible ? 'block' : 'none';
            }
        };
    }
    
    /**
     * Initialize a stepped progress bar (for multi-step tests)
     * @param {string} containerId - ID of the container element
     * @param {Array<string>} steps - Array of step labels
     * @returns {object} - API for updating the step progress
     */
    static initSteppedProgressBar(containerId, steps) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`Container with ID ${containerId} not found`);
            return null;
        }
        
        // Create stepped progress container
        const stepsContainer = document.createElement('div');
        stepsContainer.className = 'test-progress-steps';
        
        // Create steps
        steps.forEach((step, index) => {
            const stepElement = document.createElement('div');
            stepElement.className = 'test-progress-step';
            if (index === 0) {
                stepElement.classList.add('active');
            }
            
            // Add connector
            const connector = document.createElement('div');
            connector.className = 'test-progress-step-connector';
            stepElement.appendChild(connector);
            
            // Add marker
            const marker = document.createElement('div');
            marker.className = 'test-progress-step-marker';
            stepElement.appendChild(marker);
            
            // Add label
            const label = document.createElement('div');
            label.className = 'test-progress-step-label';
            label.textContent = step;
            stepElement.appendChild(label);
            
            stepsContainer.appendChild(stepElement);
        });
        
        // Add simple progress bar below steps
        const progressContainer = document.createElement('div');
        progressContainer.className = 'test-progress-container';
        
        const progressBar = document.createElement('div');
        progressBar.className = 'test-progress-bar';
        
        progressContainer.appendChild(progressBar);
        
        // Add to DOM
        container.appendChild(stepsContainer);
        container.appendChild(progressContainer);
        
        // Return API
        return {
            /**
             * Update the active step
             * @param {number} activeStepIndex - Index of the active step (0-based)
             */
            setActiveStep: (activeStepIndex) => {
                const stepElements = stepsContainer.querySelectorAll('.test-progress-step');
                stepElements.forEach((step, idx) => {
                    // Reset classes
                    step.classList.remove('active', 'completed');
                    
                    if (idx < activeStepIndex) {
                        step.classList.add('completed');
                    } else if (idx === activeStepIndex) {
                        step.classList.add('active');
                    }
                });
                
                // Update progress bar
                let progress;
                if (steps.length === 1) {
                    progress = 100; // Single step, always 100% progress
                } else {
                    progress = (activeStepIndex / (steps.length - 1)) * 100;
                }
            },
            
            /**
             * Show or hide the progress bar
             * @param {boolean} visible - Whether the progress bar should be visible
             */
            setVisible: (visible) => {
                stepsContainer.style.display = visible ? 'flex' : 'none';
                progressContainer.style.display = visible ? 'block' : 'none';
            }
        };
    }
    
    /**
     * Initialize a trial progress bar (for tests with multiple trials)
     * @param {string} containerId - ID of the container element
     * @param {number} totalTrials - Total number of trials
     * @returns {object} - API for updating the trial progress
     */
    static initTrialProgressBar(containerId, totalTrials) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`Container with ID ${containerId} not found`);
            return null;
        }
        
        // Create progress container
        const progressContainer = document.createElement('div');
        progressContainer.className = 'test-progress-container';
        
        // Create progress bar
        const progressBar = document.createElement('div');
        progressBar.className = 'test-progress-bar';
        
        // Add text
        const progressText = document.createElement('div');
        progressText.className = 'test-progress-text';
        progressText.textContent = `0/${totalTrials}`;
        
        // Add to DOM
        progressBar.appendChild(progressText);
        progressContainer.appendChild(progressBar);
        container.appendChild(progressContainer);
        
        // Return API
        return {
            /**
             * Update the trial progress
             * @param {number} currentTrial - Current trial number (0-based)
             */
            updateTrial: (currentTrial) => {
                const progress = (currentTrial / totalTrials) * 100;
                progressBar.style.width = `${progress}%`;
                progressText.textContent = `${currentTrial}/${totalTrials}`;
            },
            
            /**
             * Show or hide the progress bar
             * @param {boolean} visible - Whether the progress bar should be visible
             */
            setVisible: (visible) => {
                progressContainer.style.display = visible ? 'block' : 'none';
            }
        };
    }
} 