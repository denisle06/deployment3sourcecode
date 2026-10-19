using GpaCalculator.Models;
using GpaCalculator.Services;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;

namespace GpaCalculator.Pages;

public class IndexModel(IGradeCalculator calculator, ILogger<IndexModel> logger) : PageModel
{
    private const int DefaultRows = 4;

    [BindProperty]
    public List<UnitInput> Units { get; set; } = [];

    public GpaSummary? Summary { get; private set; }

    public void OnGet()
    {
        Units = Enumerable.Range(0, DefaultRows).Select(_ => new UnitInput()).ToList();
    }

    public IActionResult OnPost()
    {
        for (var i = 0; i < Units.Count; i++)
        {
            var unit = Units[i];
            if (!unit.IsBlank && unit.Mark is null)
                ModelState.AddModelError($"Units[{i}].Mark", "Enter a mark for this unit.");
        }

        if (Units.All(u => u.IsBlank))
            ModelState.AddModelError(string.Empty, "Add at least one unit with a mark.");

        if (!ModelState.IsValid)
        {
            // Keep rows exactly as posted so each error lines up with its row.
            if (Units.Count == 0) Units.Add(new UnitInput());
            return Page();
        }

        Summary = calculator.Calculate(Units);
        logger.LogInformation("Calculated GPA {Gpa} / WAM {Wam} for {Count} units",
            Summary.Gpa, Summary.Wam, Summary.Units.Count);

        // Re-render from the model (not the posted values) after tidying the rows.
        ModelState.Clear();
        PadRows();
        return Page();
    }

    public IActionResult OnPostSample()
    {
        ModelState.Clear();
        Units =
        [
            new() { Code = "COS10009", Name = "Introduction to Programming", Mark = 86 },
            new() { Code = "COS20007", Name = "Object Oriented Programming", Mark = 74 },
            new() { Code = "COS20019", Name = "Cloud Computing Architecture", Mark = 68 },
            new() { Code = "SWE40006", Name = "Software Deployment and Evolution", Mark = 81 },
            new() { Code = "MTH10013", Name = "Linear Algebra and Applications", Mark = 57 },
        ];
        Summary = calculator.Calculate(Units);
        PadRows();
        return Page();
    }

    /// <summary>Always leave one empty row so the user can keep typing without JavaScript.</summary>
    private void PadRows()
    {
        Units = Units.Where(u => !u.IsBlank).ToList();
        Units.Add(new UnitInput());
    }
}
