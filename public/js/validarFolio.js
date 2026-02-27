document.addEventListener("alpine:init", () => {
    Alpine.data("systemCodeGenerator", (config) => ({
        code: config.initialCode,
        userId: config.userId,
        year: config.currentYear,
        staticName: config.staticName,
        questionId: config.questionId,
        entryId: config.entryId,
        urlApi: config.urlApi,
        isLoading: false,

        initComponent() {
            // Si no hay código guardado, generamos uno nuevo
            if (!this.code) {
                this.generate();
            }
        },

        async generate() {
            // --- 1. OBTENER VARIABLES DEL DOM ---
            let nameVal = this.staticName;

            // Intentamos buscar inputs dinámicos si existen
            let nameInput = document.querySelector(
                '[data-code-tag="source_name"]',
            );
            if (nameInput && nameInput.value) {
                nameVal = nameInput.value;
            }

            let typeInput = document.querySelector(
                '[data-code-tag="source_type"]',
            );
            let typeVal = "X"; // Default
            if (typeInput) {
                // Soporte para TomSelect o Select normal
                if (typeInput.tomselect)
                    typeVal = typeInput.tomselect.getValue();
                else typeVal = typeInput.value;

                if (!typeVal) typeVal = "X";
            }

            // --- 2. CALCULAR INICIALES ---
            let initials = "XX";
            if (nameVal) {
                // Tomamos primeras letras de cada palabra
                initials = nameVal
                    .trim()
                    .split(/\s+/)
                    .map((w) => w.charAt(0))
                    .join("")
                    .toUpperCase()
                    .substring(0, 4); // Limitamos a 4 letras por si acaso
            }

            // --- 3. CÓDIGO BASE (PRE-VALIDACIÓN) ---
            let baseCode = `${this.userId}-${initials}-${this.year}-${typeVal}`;

            // --- 4. VALIDACIÓN CON SERVIDOR ---
            this.isLoading = true;

            try {
                // Construcción correcta de URL con parámetros
                const url = new URL(this.urlApi); // Usamos la URL completa pasada desde Blade

                url.searchParams.append("code", baseCode);
                url.searchParams.append("question_id", this.questionId);

                if (this.entryId) {
                    url.searchParams.append("entry_id", this.entryId);
                }

                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                if (!response.ok) throw new Error("Error en validación");

                const data = await response.json();

                // Asignamos el código final (único)
                this.code = data.unique_code;
            } catch (error) {
                console.error("Error generando folio:", error);
                // Fallback: Usamos el base si falla la API
                this.code = baseCode;
            } finally {
                this.isLoading = false;
            }
        },
    }));
});
