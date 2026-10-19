using System.ComponentModel.DataAnnotations;
using GpaCalculator.Models;
using GpaCalculator.Services;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;

namespace GpaCalculator.Pages;

public class PlannerModel(IGradeCalculator calculator) : PageModel
{
    public static readonly decimal[] Scenarios = [50, 60, 70, 80];

    [BindProperty]
    [Range(0, 100), Display(Name = "Current WAM")]
    public decimal CurrentWam { get; set; } = 65;

    [BindProperty]
    [Range(0, 1000), Display(Name = "Credit points completed")]
    public decimal CompletedCreditPoints { get; set; } = 100;

    [BindProperty]
    [Range(0.5, 1000), Display(Name = "Credit points remaining")]
    public decimal RemainingCreditPoints { get; set; } = 200;

    [BindProperty]
    [Range(0, 100), Display(Name = "Target WAM")]
    public decimal TargetWam { get; set; } = 70;

    public PlanResult? Result { get; private set; }
    public IReadOnlyList<(decimal Target, PlanResult Plan)> ScenarioResults { get; private set; } = [];

    public void OnGet(decimal? wam, decimal? cp)
    {
        if (wam is >= 0 and <= 100) CurrentWam = Math.Round(wam.Value, 2);
        if (cp is >= 0 and <= 1000) CompletedCreditPoints = cp.Value;
    }

    public IActionResult OnPost()
    {
        if (!ModelState.IsValid) return Page();

        Result = calculator.Plan(CurrentWam, CompletedCreditPoints, RemainingCreditPoints, TargetWam);
        ScenarioResults = Scenarios
            .Select(t => (t, calculator.Plan(CurrentWam, CompletedCreditPoints, RemainingCreditPoints, t)))
            .ToList();
        return Page();
    }
}
