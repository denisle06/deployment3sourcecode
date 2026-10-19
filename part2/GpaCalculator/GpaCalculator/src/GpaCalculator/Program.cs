using GpaCalculator.Models;
using GpaCalculator.Services;

var builder = WebApplication.CreateBuilder(args);

builder.Services.AddRazorPages();
builder.Services.AddHealthChecks();
builder.Services.Configure<StudentOptions>(builder.Configuration.GetSection(StudentOptions.Section));
builder.Services.AddSingleton<IGradeCalculator, GradeCalculator>();
builder.Services.AddSingleton<DeploymentInfoProvider>();

var app = builder.Build();

if (!app.Environment.IsDevelopment())
{
    app.UseExceptionHandler("/Error");
    app.UseHsts();
}

app.UseHttpsRedirection();
app.UseRouting();
app.UseAuthorization();

app.MapStaticAssets();
app.MapRazorPages().WithStaticAssets();

// Lightweight endpoints, useful for verifying the deployment from a browser or curl.
app.MapHealthChecks("/health");

app.MapPost("/api/gpa", (List<UnitInput> units, IGradeCalculator calculator) =>
{
    if (units.Any(u => u.Mark is < 0 or > 100 || u.CreditPoints <= 0))
        return Results.ValidationProblem(new Dictionary<string, string[]>
        {
            ["units"] = ["Each mark must be 0–100 and credit points must be positive."]
        });

    var summary = calculator.Calculate(units);
    return Results.Ok(new
    {
        summary.Gpa,
        summary.Wam,
        summary.TotalCreditPoints,
        summary.PassedCreditPoints,
        units = summary.Units.Select(u => new { u.Code, u.Name, u.CreditPoints, u.Mark, grade = u.Grade.Code })
    });
});

app.MapGet("/api/info", (DeploymentInfoProvider info) => Results.Ok(info.Get()));

app.Run();
