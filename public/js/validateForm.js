document.addEventListener("alpine:init", () => {
    Alpine.data("dependencyComponent", (parentId, expectedValue) => ({
        show: false,
        parentName: `answers[${parentId}]`,
        expected: expectedValue,

        init() {
            this.checkDependency();

            document.addEventListener("change", () => {
                this.checkDependency();
            });
        },

        checkDependency() {
            let elements = document.getElementsByName(this.parentName);
            let val = "";

            if (!elements.length) return;

            if (elements[0].type === "radio") {
                let checked = [...elements].find((el) => el.checked);
                val = checked ? checked.value : "";
            } else if (elements[0].type === "checkbox") {
                val = [...elements]
                    .filter((el) => el.checked)
                    .map((el) => el.value);
            } else {
                val = elements[0].value;
            }

            this.show = Array.isArray(val)
                ? val.includes(this.expected)
                : val == this.expected;
        },
    }));
});
