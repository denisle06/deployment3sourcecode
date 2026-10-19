using System.ComponentModel.DataAnnotations;

namespace GpaCalculator.Models;

/// <summary>One row the user enters on the calculator form.</summary>
public sealed class UnitInput
{
    [StringLength(12)]
    [Display(Name = "Unit code")]
    public string? Code { get; set; }

    [StringLength(80)]
    [Display(Name = "Unit name")]
    public string? Name { get; set; }

    [Range(0.5, 100, ErrorMessage = "Credit points must be between 0.5 and 100.")]
    [Display(Name = "Credit points")]
    public decimal CreditPoints { get; set; } = 12.5m;

    [Range(0, 100, ErrorMessage = "Mark must be between 0 and 100.")]
    public decimal? Mark { get; set; }

    /// <summary>A row with no code, name or mark is treated as unused and skipped.</summary>
    public bool IsBlank =>
        string.IsNullOrWhiteSpace(Code) && string.IsNullOrWhiteSpace(Name) && Mark is null;
}

/// <summary>A graded unit after calculation.</summary>
public sealed record GradedUnit(string Code, string Name, decimal CreditPoints, decimal Mark, Grade Grade);

/// <summary>Overall result for a set of units.</summary>
public sealed record GpaSummary(
    IReadOnlyList<GradedUnit> Units,
    decimal TotalCreditPoints,
    decimal PassedCreditPoints,
    decimal Gpa,
    decimal Wam,
    IReadOnlyDictionary<string, int> GradeCounts)
{
    public static GpaSummary Empty { get; } =
        new([], 0, 0, 0, 0, GradeScale.Bands.ToDictionary(b => b.Code, _ => 0));
}

public enum PlanOutcome { AlreadySecured, Achievable, NotPossible }

/// <summary>What average mark is needed in the remaining units to hit a target WAM.</summary>
public sealed record PlanResult(PlanOutcome Outcome, decimal RequiredAverage, Grade? RequiredGrade);
