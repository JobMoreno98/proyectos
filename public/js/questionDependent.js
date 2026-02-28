window.formDependency = function (parentInputName, expectedValue) {
    return {
        show: false,
        parentName: parentInputName,
        expected: String(expectedValue).trim(),

        init() {
            this.$nextTick(() => this.check());
            document.body.addEventListener("input", () => this.check());
            document.body.addEventListener("change", () => this.check());
        },

        check() {
            let els = document.querySelectorAll(
                `[name='${this.parentName}'], [name='${this.parentName}[]']`,
            );
            let val = "";

            if (els.length > 0) {
                if (els[0].type === "radio" || els[0].type === "checkbox") {
                    let checked = document.querySelector(
                        `[name='${this.parentName}']:checked, [name='${this.parentName}[]']:checked`,
                    );
                    val = checked ? checked.value : "";
                } else {
                    val = els[0].value;
                }
            }
            this.show = String(val).trim() === this.expected;
        },
    };
};
