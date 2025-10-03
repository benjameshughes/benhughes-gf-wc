/**
 * Measurement Unit Alpine.js Component
 * Handles dynamic label and constraint updates for width/drop fields
 *
 * @package BenHughes\GravityFormsWC
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('measurementUnit', function () {
        return {
            // Current selected unit
            selectedUnit: 'cm',

            // Configuration from data attributes
            formId: null,
            fieldId: null,
            widthFieldId: null,
            dropFieldId: null,

            init() {
                // Read configuration from data attributes (on ginput_container div)
                this.formId = this.$el.dataset.formId;
                this.fieldId = this.$el.dataset.fieldId;
                this.widthFieldId = this.$el.dataset.widthFieldId;
                this.dropFieldId = this.$el.dataset.dropFieldId;

                // Only proceed if width and drop fields are configured
                if (!this.widthFieldId || this.widthFieldId === '0' || !this.dropFieldId || this.dropFieldId === '0') {
                    return;
                }

                // Set up listeners after a brief delay to ensure DOM is ready
                this.$nextTick(() => {
                    this.setupListeners();
                });
            },

            setupListeners() {
                // Get the selected unit radio button (look within this.$el for radios)
                const checkedUnit = this.$el.querySelector(`input[name="input_${this.fieldId}"]:checked`);
                if (checkedUnit) {
                    this.selectedUnit = checkedUnit.value;
                }

                // Update labels, constraints, and placeholders on initial load
                this.updateFieldLabels(this.selectedUnit);
                this.updateFieldConstraints(this.selectedUnit);
                this.updateFieldPlaceholders(this.selectedUnit);

                // Listen for unit changes (look within this.$el for radios)
                const radioButtons = this.$el.querySelectorAll(`input[name="input_${this.fieldId}"]`);
                radioButtons.forEach(radio => {
                    radio.addEventListener('change', (e) => {
                        this.selectedUnit = e.target.value;
                        this.updateFieldLabels(this.selectedUnit);
                        this.updateFieldConstraints(this.selectedUnit);
                        this.updateFieldPlaceholders(this.selectedUnit);
                    });
                });
            },

            /**
             * Update field labels with selected unit
             *
             * @param {string} unit Selected unit (mm/cm/in)
             */
            updateFieldLabels(unit) {
                // Find width and drop field labels
                const widthLabel = document.querySelector(`#field_${this.formId}_${this.widthFieldId} .gfield_label`);
                const dropLabel = document.querySelector(`#field_${this.formId}_${this.dropFieldId} .gfield_label`);

                if (!widthLabel || !dropLabel) return;

                // Store original labels if not already stored
                if (!widthLabel.dataset.originalText) {
                    widthLabel.dataset.originalText = widthLabel.textContent.trim();
                }
                if (!dropLabel.dataset.originalText) {
                    dropLabel.dataset.originalText = dropLabel.textContent.trim();
                }

                // Update labels with unit (lowercase)
                widthLabel.textContent = `${widthLabel.dataset.originalText} (${unit})`;
                dropLabel.textContent = `${dropLabel.dataset.originalText} (${unit})`;
            },

            /**
             * Update field constraints (min/max/step) and helper text
             *
             * @param {string} unit Selected unit (mm/cm/in)
             */
            updateFieldConstraints(unit) {
                // Find width and drop input fields
                const widthInput = document.querySelector(`#input_${this.formId}_${this.widthFieldId}`);
                const dropInput = document.querySelector(`#input_${this.formId}_${this.dropFieldId}`);

                if (!widthInput || !dropInput) return;

                // Get original max/min values (assuming they're in cm)
                const widthMaxCm = parseFloat(widthInput.dataset.originalMax || widthInput.max || 300);
                const widthMinCm = parseFloat(widthInput.dataset.originalMin || widthInput.min || 0);
                const dropMaxCm = parseFloat(dropInput.dataset.originalMax || dropInput.max || 300);
                const dropMinCm = parseFloat(dropInput.dataset.originalMin || dropInput.min || 0);

                // Store original values if not already stored
                if (!widthInput.dataset.originalMax) {
                    widthInput.dataset.originalMax = widthMaxCm;
                    widthInput.dataset.originalMin = widthMinCm;
                    dropInput.dataset.originalMax = dropMaxCm;
                    dropInput.dataset.originalMin = dropMinCm;
                }

                let widthMax, widthMin, dropMax, dropMin, step;

                // Convert max/min based on selected unit
                switch(unit) {
                    case 'mm':
                        widthMax = (widthMaxCm * 10).toString();
                        widthMin = (widthMinCm * 10).toString();
                        dropMax = (dropMaxCm * 10).toString();
                        dropMin = (dropMinCm * 10).toString();
                        step = '1';
                        break;
                    case 'in':
                        widthMax = (widthMaxCm / 2.54).toFixed(0);
                        widthMin = (widthMinCm / 2.54).toFixed(0);
                        dropMax = (dropMaxCm / 2.54).toFixed(0);
                        dropMin = (dropMinCm / 2.54).toFixed(0);
                        step = '0.25';
                        break;
                    case 'cm':
                    default:
                        widthMax = widthMaxCm.toString();
                        widthMin = widthMinCm.toString();
                        dropMax = dropMaxCm.toString();
                        dropMin = dropMinCm.toString();
                        step = '0.1';
                        break;
                }

                // Update input attributes
                widthInput.max = widthMax;
                widthInput.min = widthMin;
                widthInput.step = step;
                dropInput.max = dropMax;
                dropInput.min = dropMin;
                dropInput.step = step;

                // Update range instruction text (Gravity Forms helper text)
                this.updateRangeInstructions(this.widthFieldId, widthMin, widthMax);
                this.updateRangeInstructions(this.dropFieldId, dropMin, dropMax);
            },

            /**
             * Update Gravity Forms range instruction text
             *
             * @param {string} fieldId Field ID
             * @param {string} min Minimum value
             * @param {string} max Maximum value
             */
            updateRangeInstructions(fieldId, min, max) {
                // Get unit text (lowercase)
                const unitText = this.selectedUnit;

                // Find instruction element for this field
                const instruction = document.querySelector(`#gfield_instruction_${this.formId}_${fieldId}`);

                if (instruction) {
                    // Store original text if not already stored
                    if (!instruction.dataset.originalText) {
                        instruction.dataset.originalText = instruction.textContent;
                    }

                    // Update with new min/max values including unit
                    instruction.textContent = `Please enter a value between ${min}${unitText} and ${max}${unitText}.`;
                }

                // Also update any .gfield_description.instruction elements
                const descInstructions = document.querySelectorAll(`#field_${this.formId}_${fieldId} .gfield_description.instruction`);
                descInstructions.forEach(desc => {
                    if (!desc.dataset.originalText) {
                        desc.dataset.originalText = desc.textContent;
                    }
                    desc.textContent = `Please enter a value between ${min}${unitText} and ${max}${unitText}.`;
                });
            },

            /**
             * Update field placeholders based on selected unit
             * Converts from base value (100cm) to selected unit
             *
             * @param {string} unit Selected unit (mm/cm/in)
             */
            updateFieldPlaceholders(unit) {
                // Find width and drop input fields
                const widthInput = document.querySelector(`#input_${this.formId}_${this.widthFieldId}`);
                const dropInput = document.querySelector(`#input_${this.formId}_${this.dropFieldId}`);

                if (!widthInput || !dropInput) return;

                // Base placeholder value in cm
                const basePlaceholderCm = 100;
                let placeholderValue;

                // Convert placeholder based on selected unit
                switch(unit) {
                    case 'mm':
                        placeholderValue = (basePlaceholderCm * 10).toString(); // 1000
                        break;
                    case 'in':
                        placeholderValue = (basePlaceholderCm / 2.54).toFixed(1); // 39.4
                        break;
                    case 'cm':
                    default:
                        placeholderValue = basePlaceholderCm.toString(); // 100
                        break;
                }

                // Update placeholder attributes
                widthInput.placeholder = placeholderValue;
                dropInput.placeholder = placeholderValue;
            }
        };
    });
});
