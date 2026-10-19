// Adds and removes unit rows on the calculator, keeping the form field
// indexes (Units[0], Units[1], ...) contiguous so ASP.NET Core model binding works.
(() => {
    const rows = document.getElementById("unit-rows");
    const template = document.getElementById("row-template");
    const addButton = document.getElementById("add-row");
    if (!rows || !template || !addButton) return;

    const reindex = () => {
        rows.querySelectorAll("tr.unit-row").forEach((row, i) => {
            row.querySelectorAll("[name], [id], [for], [data-valmsg-for]").forEach(el => {
                for (const attr of ["name", "id", "for", "data-valmsg-for"]) {
                    const value = el.getAttribute(attr);
                    if (value) {
                        el.setAttribute(attr, value
                            .replace(/Units\[\d+\]/g, `Units[${i}]`)
                            .replace(/Units_\d+__/g, `Units_${i}__`));
                    }
                }
            });
        });
    };

    addButton.addEventListener("click", () => {
        const index = rows.querySelectorAll("tr.unit-row").length;
        const html = template.innerHTML.replaceAll("__i__", index);
        rows.insertAdjacentHTML("beforeend", html);
        rows.lastElementChild.querySelector("input")?.focus();
    });

    rows.addEventListener("click", e => {
        const button = e.target.closest("[data-remove-row]");
        if (!button) return;
        const all = rows.querySelectorAll("tr.unit-row");
        if (all.length === 1) {
            // Keep one row; just clear it.
            all[0].querySelectorAll("input").forEach(input => {
                input.value = input.name.endsWith("CreditPoints") ? "12.5" : "";
            });
            return;
        }
        button.closest("tr").remove();
        reindex();
    });

    document.getElementById("unit-form")?.addEventListener("submit", reindex);

    if (document.getElementById("results")) {
        document.getElementById("results").scrollIntoView({ behavior: "smooth", block: "start" });
    }
})();
