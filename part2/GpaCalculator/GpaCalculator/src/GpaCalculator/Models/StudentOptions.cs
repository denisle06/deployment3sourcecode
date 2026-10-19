namespace GpaCalculator.Models;

/// <summary>
/// Shown in the site footer. Set in appsettings.json, or override on Azure with
/// the App Settings "Student__Name" and "Student__Id".
/// </summary>
public sealed class StudentOptions
{
    public const string Section = "Student";

    public string Name { get; set; } = "";
    public string Id { get; set; } = "";
    public string Unit { get; set; } = "";
}
