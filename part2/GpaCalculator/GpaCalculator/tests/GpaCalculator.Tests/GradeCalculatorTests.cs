using GpaCalculator.Models;
using GpaCalculator.Services;

namespace GpaCalculator.Tests;

public class GradeScaleTests
{
    [Theory]
    [InlineData(100, "HD")]
    [InlineData(80, "HD")]
    [InlineData(79.9, "D")]
    [InlineData(70, "D")]
    [InlineData(60, "C")]
    [InlineData(50, "P")]
    [InlineData(49.9, "N")]
    [InlineData(0, "N")]
    public void ForMark_ReturnsExpectedGrade(double mark, string expected)
    {
        Assert.Equal(expected, GradeScale.ForMark((decimal)mark).Code);
    }

    [Theory]
    [InlineData(-1)]
    [InlineData(100.1)]
    public void ForMark_RejectsOutOfRange(double mark)
    {
        Assert.Throws<ArgumentOutOfRangeException>(() => GradeScale.ForMark((decimal)mark));
    }
}

public class GradeCalculatorTests
{
    private readonly GradeCalculator _calc = new();

    [Fact]
    public void Calculate_WeightsByCreditPoints()
    {
        var units = new List<UnitInput>
        {
            new() { Code = "A", CreditPoints = 12.5m, Mark = 85 }, // HD = 4
            new() { Code = "B", CreditPoints = 25m,   Mark = 55 }, // P  = 1
        };

        var result = _calc.Calculate(units);

        // GPA = (4*12.5 + 1*25) / 37.5 = 2.00 ; WAM = (85*12.5 + 55*25) / 37.5 = 65.00
        Assert.Equal(2.00m, result.Gpa);
        Assert.Equal(65.00m, result.Wam);
        Assert.Equal(37.5m, result.TotalCreditPoints);
    }

    [Fact]
    public void Calculate_IgnoresBlankRows_AndCountsGrades()
    {
        var units = new List<UnitInput>
        {
            new() { Code = "a", Mark = 90 },
            new(),
            new() { Code = "b", Mark = 40 },
        };

        var result = _calc.Calculate(units);

        Assert.Equal(2, result.Units.Count);
        Assert.Equal("A", result.Units[0].Code);
        Assert.Equal(1, result.GradeCounts["HD"]);
        Assert.Equal(1, result.GradeCounts["N"]);
        Assert.Equal(12.5m, result.PassedCreditPoints);
    }

    [Fact]
    public void Calculate_EmptyInput_ReturnsEmptySummary()
    {
        var result = _calc.Calculate([new UnitInput()]);
        Assert.Empty(result.Units);
        Assert.Equal(0m, result.Gpa);
    }

    [Fact]
    public void Plan_Achievable_ReturnsRequiredAverage()
    {
        // (70*300 - 65*100) / 200 = 72.5
        var plan = _calc.Plan(65, 100, 200, 70);
        Assert.Equal(PlanOutcome.Achievable, plan.Outcome);
        Assert.Equal(72.5m, plan.RequiredAverage);
        Assert.Equal("D", plan.RequiredGrade!.Code);
    }

    [Fact]
    public void Plan_TooHigh_IsNotPossible()
    {
        var plan = _calc.Plan(50, 250, 50, 80);
        Assert.Equal(PlanOutcome.NotPossible, plan.Outcome);
    }

    [Fact]
    public void Plan_TargetBelowCurrent_WithFewUnitsLeft_IsSecured()
    {
        // (75*300 - 80*287.5) / 12.5 = -40, so any mark keeps the target
        var plan = _calc.Plan(80, 287.5m, 12.5m, 75);
        Assert.Equal(PlanOutcome.AlreadySecured, plan.Outcome);
    }
}
