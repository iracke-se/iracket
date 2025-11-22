using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Hosting;
using Serilog;
using Serilog.Formatting.Json;
using Serilog.Sinks.File;
using Services;
using Services.Interfaces;
using Shared.Enums;
using System;
using System.Globalization;
using System.IO;
using System.Reflection;
using System.Threading.Tasks;

namespace WebScraper
{
    partial class Program
    {
        private static async Task Main(string[] args)
        {
            var host = AppStartup();

            var _playerListSerice = host.Services.GetRequiredService<IPlayerListService>();
            var _rankServuce = host.Services.GetRequiredService<IRanksService>();
            var _liveCenterService = host.Services.GetRequiredService<ILiveCenterService>();
            var _seriesService = host.Services.GetRequiredService<ISeriesService>();



         

            Console.ForegroundColor = ConsoleColor.White;
            int selected = 0;

            if (args.Length > 0)
            {
                selected = int.Parse(args[0]);
            }
            else
            {
                Console.WriteLine($"1 Player List (Licensed players)");
                Console.WriteLine($"2 Player List (Transitions)");
                Console.WriteLine($"3 Get Live Center");
                Console.WriteLine($"4 Get Series");
                Console.WriteLine($"5 Get Ranking");

                selected = int.Parse(Console.ReadLine());
            }

            switch (selected)
            {
                case 1:
                    {
                        Console.Title = "PlayerList (Licensed players)";
                        await _playerListSerice.GetPlayerListLicensedPlayers();
                        break;
                    }
                case 2:
                    {
                        Console.Title = "PlayerList (Transitions)";
                        await _playerListSerice.GetPlayerListingTransitions();
                        break;
                    }
                case 3:
                    {
                        Console.WriteLine("Set number of instances");
                        int instances = 1;
                        if (args.Length > 0)
                        {
                            instances = int.Parse(args[1]);
                        }
                        else
                        {
                            int.TryParse(Console.ReadLine(), out instances);
                        }


                        if (instances <= 1)
                        {
                            Console.Title = "GetLiveCenter";

                            await _liveCenterService.GetLiveCenter();
                        }
                        else
                        {
                            await _liveCenterService.StartProcesses(instances);
                        }
                        break;
                    }
                case 4:
                    {
                        Console.Title = "GetSeries";


                        var period = "";
                        DropDownDirection? destination = null;
                        if (args.Length > 0)
                        {
                            period = args[1];
                            destination = (DropDownDirection)int.Parse(args[2]);
                        }
                        else
                        {


                            Console.WriteLine("Write period to scrape ex : 2020");
                            Console.WriteLine("Or Press ENTER to continue for all");
                            period = Console.ReadLine();

                            if (!String.IsNullOrEmpty(period))
                            {
                                Console.WriteLine($"{(int)DropDownDirection.GreaterOrEqualTo} for Greater Equal Date \n" +
                                $"{(int)DropDownDirection.LessOrEqualTo} Less Or Equal Date");
                                var direction = int.Parse(Console.ReadLine());
                                destination = (DropDownDirection)direction;
                            }




                        }


                        var parsedStatus = int.TryParse(period, out int periodParsed);

                        if (parsedStatus)
                        {
                            await _seriesService.GetSeries(periodParsed, destination);
                        }
                        else
                        {
                            await _seriesService.GetSeries();
                        }




                        break;
                    }
                case 5:
                    {
                        Console.ForegroundColor = ConsoleColor.Green;
                        Console.WriteLine($"{(int)Gender.Female} for {Gender.Female} \n" +
                            $"{(int)Gender.Male} for {Gender.Male}");

                        int gender = 0;
                        bool parsValid = false;
                        var directionEnum = DropDownDirection.GreaterOrEqualTo;

                        var date = DateTime.Now;
                        if (args.Length > 0)
                        {
                            gender = int.Parse(args[1]);
                            date = DateTime.Parse(args[2]);
                            directionEnum = (DropDownDirection)int.Parse(args[3]);

                            Console.WriteLine(gender);
                            Console.WriteLine(date);
                            Console.WriteLine(directionEnum.ToString());

                        }
                        else
                        {
                            gender = int.Parse(Console.ReadLine());
                            Console.ForegroundColor = ConsoleColor.Magenta;
                            Console.WriteLine($"Enter Date in format YYYY.MM.DD ex : {DateTime.UtcNow.ToString("yyyy.MM.dd")} \n" +
                                $"Empty will take all");

                            parsValid = DateTime.TryParseExact(Console.ReadLine(), "yyyy.MM.dd", CultureInfo.InvariantCulture, DateTimeStyles.None, out date);
                        }


                        var genderEnum = (Gender)gender;




                        if (parsValid)
                        {
                            Console.ForegroundColor = ConsoleColor.Blue;
                            Console.WriteLine($"{(int)DropDownDirection.GreaterOrEqualTo} for Greater Equal Date \n" +
                                $"{(int)DropDownDirection.LessOrEqualTo} Less Or Equal Date");
                            var direction = int.Parse(Console.ReadLine());
                            directionEnum = (DropDownDirection)direction;
                        }

                        Console.ForegroundColor = ConsoleColor.White;
                        Console.Title = "Get Ranking";
                        await _rankServuce.GetRanking(genderEnum, date, directionEnum);
                        break;
                    }
                case 6:
                    {
                        Console.Title = "GetLiveCenter";
                        if (args.Length > 0)
                        {
                            var take = int.Parse(args[1]);
                            var skip = int.Parse(args[2]);

                            Console.WriteLine($" SKIP : {skip} TAKE {take}");
                            await _liveCenterService.GetLiveCenter(take, skip);
                        }
                        else
                        {
                            await _liveCenterService.GetLiveCenter();
                        }

                        break;
                    }

                default:
                    {
                        Console.WriteLine("Invalid option");
                        break;
                    }
            }
            Environment.Exit(0);

            Console.WriteLine("Press any key to continue...");
            Console.ReadLine();
        }

        private static void BuildConfig(IConfigurationBuilder builder)
        {
            // Check the current directory that the application is running on
            // Then once the file 'appsetting.json' is found, we are adding it.
            // We add env variables, which can override the configs in appsettings.json
            builder.SetBasePath(Directory.GetCurrentDirectory())
                .AddJsonFile("appsettings.json", optional: false, reloadOnChange: true)
                .AddEnvironmentVariables();
        }

        private static IHost AppStartup()
        {
            var builder = new ConfigurationBuilder();
            BuildConfig(builder);

            // Specifying the configuration for serilog
            Log.Logger = new LoggerConfiguration() // initiate the logger configuration
                            .ReadFrom.Configuration(builder.Build()) // connect serilog to our configuration folder
                            .Enrich.FromLogContext() //Adds more information to our logs from built in Serilog
                            .WriteTo.Console()
                             .WriteTo.File($"{DateTime.Now.ToString("MM.dd.yyyy")}.json", outputTemplate: "{Timestamp:yyyy-MM-dd HH:mm:ss.fff zzz} [{Level:u3}] {Message:lj}{NewLine}{Exception}", rollingInterval: RollingInterval.Day)
                            .CreateLogger(); //initialise the logger

            //Log.Logger.Information("Application Starting");

            var host = Host.CreateDefaultBuilder() // Initialising the Host
                        .ConfigureServices((context, services) =>
                        { // Adding the DI container for configuration
                            services.AddSingleton<IBrowserService, BrowserService>();
                            services.AddSingleton<IPlayerListService, PlayerListService>();
                            services.AddSingleton<IRanksService, RanksService>();
                            services.AddSingleton<ILiveCenterService, LiveCenterService>();
                            services.AddSingleton<ISeriesService, SeriesService>();
                            services.AddSingleton<ISendToApiService, SendToApiService>();

                        })
                        .UseSerilog()
                        .Build(); // Build the Host

            return host;
        }
        public static DateTime GetLinkerTime(Assembly assembly)
        {
            const string BuildVersionMetadataPrefix = "+build";

            var attribute = assembly.GetCustomAttribute<AssemblyInformationalVersionAttribute>();
            if (attribute?.InformationalVersion != null)
            {
                var value = attribute.InformationalVersion;
                var index = value.IndexOf(BuildVersionMetadataPrefix);
                if (index > 0)
                {
                    value = value[(index + BuildVersionMetadataPrefix.Length)..];
                    return DateTime.ParseExact(value, "yyyy-MM-ddTHH:mm:ss:fffZ", CultureInfo.InvariantCulture);
                }
            }

            return default;
        }
    }
}