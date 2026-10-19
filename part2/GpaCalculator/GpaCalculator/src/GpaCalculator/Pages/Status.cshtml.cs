using GpaCalculator.Services;
using Microsoft.AspNetCore.Mvc.RazorPages;

namespace GpaCalculator.Pages;

public class StatusModel(DeploymentInfoProvider provider) : PageModel
{
    public DeploymentInfo Info { get; private set; } = default!;

    public void OnGet() => Info = provider.Get();
}
