using System.Diagnostics;
using System.Runtime.InteropServices;

namespace GpaCalculator.Services;

public sealed record DeploymentInfo(
    bool RunningOnAzure,
    string SiteName,
    string HostName,
    string Region,
    string Sku,
    string InstanceId,
    string Environment,
    string MachineName,
    string Framework,
    string OperatingSystem,
    DateTimeOffset StartedUtc,
    TimeSpan Uptime,
    DateTimeOffset ServerTimeUtc);

/// <summary>
/// Reads the environment variables Azure App Service sets on every web app,
/// so the Status page can prove where the app is running.
/// Only non-secret values are exposed.
/// </summary>
public sealed class DeploymentInfoProvider(IWebHostEnvironment env)
{
    private static readonly DateTimeOffset Started =
        Process.GetCurrentProcess().StartTime.ToUniversalTime();

    public DeploymentInfo Get()
    {
        var siteName = Env("WEBSITE_SITE_NAME");
        var instance = Env("WEBSITE_INSTANCE_ID");
        var now = DateTimeOffset.UtcNow;

        return new DeploymentInfo(
            RunningOnAzure: siteName is not null,
            SiteName: siteName ?? "Local machine",
            HostName: Env("WEBSITE_HOSTNAME") ?? "localhost",
            Region: Env("REGION_NAME") ?? "—",
            Sku: Env("WEBSITE_SKU") ?? "—",
            InstanceId: instance is { Length: > 12 } ? instance[..12] + "…" : instance ?? "—",
            Environment: env.EnvironmentName,
            MachineName: System.Environment.MachineName,
            Framework: RuntimeInformation.FrameworkDescription,
            OperatingSystem: RuntimeInformation.OSDescription,
            StartedUtc: Started,
            Uptime: now - Started,
            ServerTimeUtc: now);
    }

    private static string? Env(string name)
    {
        var value = System.Environment.GetEnvironmentVariable(name);
        return string.IsNullOrWhiteSpace(value) ? null : value;
    }
}
