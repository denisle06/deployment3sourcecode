namespace GpaCalculator.Models;

/// <summary>A Swinburne-style grade band.</summary>
public sealed record Grade(string Code, string Name, int MinMark, int GradePoint);

public static class GradeScale
{
    // Ordered from highest to lowest so the first match wins.
    public static readonly IReadOnlyList<Grade> Bands =
    [
        new("HD", "High Distinction", 80, 4),
        new("D",  "Distinction",      70, 3),
        new("C",  "Credit",           60, 2),
        new("P",  "Pass",             50, 1),
        new("N",  "Fail",              0, 0),
    ];

    public static Grade ForMark(decimal mark)
    {
        if (mark is < 0 or > 100)
            throw new ArgumentOutOfRangeException(nameof(mark), mark, "Mark must be between 0 and 100.");

        return Bands.First(b => mark >= b.MinMark);
    }
}
