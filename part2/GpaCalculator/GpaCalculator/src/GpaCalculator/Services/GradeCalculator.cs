using GpaCalculator.Models;

namespace GpaCalculator.Services;

public interface IGradeCalculator
{
    GpaSummary Calculate(IEnumerable<UnitInput> units);
    PlanResult Plan(decimal currentWam, decimal completedCreditPoints, decimal remainingCreditPoints, decimal targetWam);
}

/// <summary>
/// GPA = credit-point-weighted average of grade points (4-point scale).
/// WAM = credit-point-weighted average of marks.
/// </summary>
public sealed class GradeCalculator : IGradeCalculator
{
    public GpaSummary Calculate(IEnumerable<UnitInput> units)
    {
        var graded = units
            .Where(u => !u.IsBlank && u.Mark is not null)
            .Select(u => new GradedUnit(
                Code: string.IsNullOrWhiteSpace(u.Code) ? "—" : u.Code.Trim().ToUpperInvariant(),
                Name: u.Name?.Trim() ?? "",
                CreditPoints: u.CreditPoints,
                Mark: u.Mark!.Value,
                Grade: GradeScale.ForMark(u.Mark.Value)))
            .ToList();

        if (graded.Count == 0)
            return GpaSummary.Empty;

        var totalCp = graded.Sum(u => u.CreditPoints);
        var passedCp = graded.Where(u => u.Grade.GradePoint > 0).Sum(u => u.CreditPoints);
        var gpa = graded.Sum(u => u.Grade.GradePoint * u.CreditPoints) / totalCp;
        var wam = graded.Sum(u => u.Mark * u.CreditPoints) / totalCp;

        var counts = GradeScale.Bands.ToDictionary(
            b => b.Code,
            b => graded.Count(u => u.Grade.Code == b.Code));

        return new GpaSummary(graded, totalCp, passedCp,
            Math.Round(gpa, 2), Math.Round(wam, 2), counts);
    }

    public PlanResult Plan(decimal currentWam, decimal completedCreditPoints, decimal remainingCreditPoints, decimal targetWam)
    {
        if (remainingCreditPoints <= 0)
            throw new ArgumentOutOfRangeException(nameof(remainingCreditPoints), "Remaining credit points must be positive.");

        var totalCp = completedCreditPoints + remainingCreditPoints;
        var required = (targetWam * totalCp - currentWam * completedCreditPoints) / remainingCreditPoints;
        required = Math.Round(required, 2);

        if (required <= 0)
            return new PlanResult(PlanOutcome.AlreadySecured, 0, null);
        if (required > 100)
            return new PlanResult(PlanOutcome.NotPossible, required, null);

        return new PlanResult(PlanOutcome.Achievable, required, GradeScale.ForMark(required));
    }
}
