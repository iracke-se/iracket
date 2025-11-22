# iRacket Web Scraper Analysis

## Overview

The iRacket Web Scraper is a .NET console application that extracts table tennis data from **profixio.com** (Swedish Table Tennis Federation's platform). It uses **PuppeteerSharp** for headless browser automation to navigate web pages, execute JavaScript, and extract structured data which is then sent to the iRacket API.

## Technology Stack

- **.NET Core** - Console application
- **PuppeteerSharp** - Headless Chrome browser automation
- **Polly** - Resilience and transient-fault-handling (circuit breaker pattern)
- **Serilog** - Structured logging
- **RestSharp/HttpClient** - HTTP requests to send data to API
- **Newtonsoft.Json** - JSON serialization

## Architecture

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Program.cs    │────▶│    Services      │────▶│  SendToApi      │
│  (Entry Point)  │     │                  │     │   Service       │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                               │
                               ▼
                        ┌──────────────────┐
                        │  BrowserService  │
                        │  (PuppeteerSharp)│
                        └──────────────────┘
                               │
                               ▼
                        ┌──────────────────┐
                        │   profixio.com   │
                        └──────────────────┘
```

## Project Structure

```
docs/scraper/
├── WebScraper/
│   ├── Program.cs              # Entry point with menu system
│   ├── appsettings.json        # Configuration (URLs, timeouts)
│   └── Scripts/                # JavaScript extraction functions
│       ├── GetRanks.js
│       ├── GetTransitions.js
│       ├── DivisionData.js
│       ├── GetSesons.js
│       └── ...
├── Services/
│   ├── BrowserService.cs       # Browser initialization
│   ├── PlayerListService.cs    # Player data scraping
│   ├── RanksService.cs         # Rankings scraping
│   ├── LiveCenterService.cs    # Live match data
│   ├── SeriesService.cs        # Series/seasons data
│   ├── SendToApiService.cs     # HTTP POST to iRacket API
│   └── Interfaces/             # Service interfaces
└── Shared/
    ├── Helpers/
    │   └── JavascriptHelpers.cs # JS script generators
    ├── Models/                  # Data models (DTOs)
    └── Enums/
```

## Scraping Modes

The scraper offers 5 main operation modes, selected via command-line arguments or interactive menu:

### 1. Player List - Licensed Players (Option 1)

**Service:** `PlayerListService.GetPlayerListLicensedPlayers()`

**What it scrapes:**
- All licensed players from all clubs
- Iterates through all periods and clubs
- Extracts player details from table rows

**Data extracted per player:**
- Surname
- First Name
- Sex
- Date of Birth
- License Type
- Current Player Class

**Navigation flow:**
1. Click "Spelar" (Players) menu item
2. Get all periods from dropdown
3. For each period, get all clubs from dropdown
4. For each club, extract player table data

**API Endpoint:** Configured in `PlayerListPlayersUrl` section

### 2. Player List - Transitions (Option 2)

**Service:** `PlayerListService.GetPlayerListingTransitions()`

**What it scrapes:**
- Player transfers between clubs
- Club change history

**Data extracted per transition:**
- Surname
- First Name
- Born (birth year)
- From (previous club)
- To (new club)
- Game Completion Date

**JavaScript extraction (GetTransitions.js):**
```javascript
{
    Surname: rows[i].cells[0].innerText,
    FirstName: rows[i].cells[1].innerText,
    Born: rows[i].cells[2].innerText,
    From: rows[i].cells[3].innerText,
    To: rows[i].cells[4].innerText,
    GameCompletionDate: rows[i].cells[5].innerText
}
```

**API Endpoint:** Configured in `PlayerListTransitionsUrl` section

### 3. Live Center (Option 3)

**Service:** `LiveCenterService.GetLiveCenter()`

**What it scrapes:**
- Live and historical match data
- Team matches with detailed game sets
- Individual player scores within matches

**Data hierarchy:**
```
Division
└── Period
    └── TeamDetails
        └── Matches
            └── GameSets
                └── SetDetails (if ScrapeLiveCenterMatchDetails=true)
```

**Key features:**
- Supports parallel processing via multiple instances
- Can spawn multiple console processes for faster scraping
- Deep nesting - clicks through teams → matches → individual games

**Selectors used:**
- Division filter: `#filter4_id`
- Period filter: `#filter1_id`
- Matches table: `#matches > div > table tr`
- Match details: `#matchtable`

**API Endpoint:** Configured in `LiveCenterUrl` section

### 4. Series (Option 4)

**Service:** `SeriesService.GetSeries()`

**What it scrapes:**
- League/series standings
- Team statistics
- Match results within series
- Player statistics per team

**Data structure:**
```
SeriesDto
├── SeriesName
├── Period
└── Sessions[]
    ├── SesionTableData (team standings)
    ├── MatcherFirstAproach (match results)
    ├── MatchStatisticPerTeam
    └── MatchStatisticPerPlayer
```

**Features:**
- Period filtering (greater/less than a specific year)
- Handles nested series structures
- Three tabs of data per session:
  1. Match results
  2. Team statistics
  3. Player statistics

**API Endpoint:** Configured in `SeriesUrl` section

### 5. Rankings (Option 5)

**Service:** `RanksService.GetRanking()`

**What it scrapes:**
- Player rankings by gender and division
- Ranking points and details
- Historical ranking data by period

**Data extracted per ranked player:**
- Investment (ranking position + change)
- Name
- Born (birth year)
- Club
- Points

**JavaScript extraction (GetRanks.js):**
```javascript
{
    Investment: rows[i].cells[0].innerText + ' ' + rows[i].cells[1].innerText,
    Name: rows[i].cells[2].innerText,
    Born: rows[i].cells[3].innerText,
    Club: rows[i].cells[4].innerText,
    Point: rows[i].cells[5].innerText + ' ' + rows[i].cells[6].innerText
}
```

**Filters:**
- Gender (Male/Female)
- Date range (greater/less than specific date)
- Division

**API Endpoint:** Configured in `RanksUrl` section

## Core Components

### BrowserService

Initializes PuppeteerSharp with headless Chrome:

```csharp
await new BrowserFetcher().DownloadAsync(BrowserFetcher.DefaultRevision);

var browser = await Puppeteer.LaunchAsync(new LaunchOptions
{
    Headless = true,
    Args = new[] { "--no-sandbox" }
});

await page.GoToAsync(_config.GetValue<string>("MainUrl"));
```

**Configuration:**
- `MainUrl`: Base URL (profixio.com)
- `HeadlessBrowser`: true/false for debugging
- `ChromiumPath`: Custom Chrome path (for Linux deployment)

### JavascriptHelpers

Generates JavaScript code that runs in the browser context to extract data:

**Key methods:**
- `GetAllValuesFromDropDown(id)` - Extracts dropdown options
- `GetTableData(selector)` - Generic table extraction
- `GetRanksFunction()` - Loads external JS file
- `GetTransitions()` - Player transfer data

**Pattern:** Most complex extractions load external `.js` files from the Scripts folder for maintainability.

### SendToApiService

Posts scraped data to iRacket API:

```csharp
var data = JsonConvert.SerializeObject(databla);
var paramss = new Dictionary<string, string>();
paramss.Add("data", data);

using (HttpClient client = new HttpClient())
{
    HttpResponseMessage response = await client.PostAsync(
        $"{url}\\{path}",
        new FormUrlEncodedContent(paramss)
    );
}
```

## Resilience Patterns

### Circuit Breaker (Polly)

All services use Polly's circuit breaker pattern:

```csharp
public AsyncCircuitBreakerPolicy policyRetry3Times = Policy
    .Handle<Exception>()
    .CircuitBreakerAsync(3, TimeSpan.FromMilliseconds(1000),
        onBreak: (exception, time) => Console.WriteLine(exception.Message),
        onReset: () => Console.WriteLine("Reset")
    );
```

- Breaks after 3 consecutive failures
- Resets after 1 second

### Retry Logic

Manual retry loops for critical operations:

```csharp
int numberofTimes = 0;
while (true)
{
    try
    {
        tableResults = await page.EvaluateExpressionAsync<...>(query);
        break;
    }
    catch (Exception e)
    {
        if (e is TargetClosedException)
            Environment.Exit(1);
        if (numberofTimes > 10)
            Environment.Exit(1);
        numberofTimes++;
    }
}
```

### Error Handling

- `TargetClosedException` - Browser crashed, exit immediately
- "Session closed" - Browser session lost, exit
- Index decrement on failure - Retry same iteration

## CSS Selectors Reference

### Main Navigation
```css
#hoved-meny > li:nth-child(2) > a    /* Player List */
#hoved-meny > li:nth-child(3) > a    /* Series */
#hoved-meny > li:nth-child(4) > a    /* Rankings */
#hoved-meny > li:nth-child(5) > a    /* Live Center */
```

### Dropdowns
```css
#periode     /* Period selector */
#klubbid     /* Club selector */
#filter1_id  /* Live Center period */
#filter4_id  /* Live Center division */
```

### Tables
```css
.table-condensed                                    /* Player list table */
#matches > div > table tr                          /* Live Center matches */
#tabell_std tr                                     /* Series standings */
#main-col > div.maincontent > table...tbody tr     /* Rankings table */
```

## Configuration (appsettings.json)

```json
{
  "MainUrl": "https://www.profixio.com/fx/...",
  "HeadlessBrowser": true,
  "ChromiumPath": "",
  "ScrapeLiveCenterMatchDetails": false,

  "PlayerListPlayersUrl": {
    "Host": "https://api.iracket.com",
    "Action": "players"
  },
  "PlayerListTransitionsUrl": {
    "Host": "https://api.iracket.com",
    "Action": "transitions"
  },
  "LiveCenterUrl": {
    "Host": "https://api.iracket.com",
    "Action": "livecenter"
  },
  "SeriesUrl": {
    "Host": "https://api.iracket.com",
    "Action": "series"
  },
  "RanksUrl": {
    "Host": "https://api.iracket.com",
    "Action": "ranks"
  }
}
```

## Data Models

### Key DTOs

| Model | Purpose |
|-------|---------|
| `RankDto` | Rankings with period, division, gender, and results |
| `PlayerListDto` | Licensed players by period and club |
| `TransitionsDto` | Player club transfers |
| `LiveCenterV2` | Match data with teams and game sets |
| `SeriesDto` | Series standings and statistics |
| `Club` | Club with list of players |
| `TeamDetails` | Team match information |
| `Mathc` | Individual match with sets |
| `GameSets` | Set scores and details |

### Example: RankDto

```csharp
public class RankDto
{
    public string Period { get; set; }
    public string Devision { get; set; }
    public Gender Gender { get; set; }
    public List<RankResuls> Data { get; set; }
}
```

## Running the Scraper

### Command Line

```bash
# Interactive mode
dotnet run

# With arguments
dotnet run 1                          # Licensed players
dotnet run 2                          # Transitions
dotnet run 3 4                        # Live Center with 4 instances
dotnet run 4 2020 1                   # Series from 2020+
dotnet run 5 1 2023.01.01 1           # Male rankings from 2023+
```

### Building for Deployment

```bash
# Ubuntu build
dotnet publish -c release -r ubuntu.16.04-x64 --self-contained

# Windows build
dotnet publish -c release -r win-x64 --self-contained
```

## Parallel Processing

The Live Center service supports spawning multiple processes:

```csharp
var divisionsPerConsole = divisionResults.Length / numberOfProcesses;
for (int i = 0; i < numberOfProcesses; i++)
{
    ProcessStartInfo startInfo = new ProcessStartInfo
    {
        FileName = "WebScraper.exe",
        Arguments = $"6 {divisionsPerConsole} {skip}",
        UseShellExecute = true,
    };
    Process.Start(startInfo);
    skip += divisionsPerConsole;
}
```

This divides divisions across multiple console windows for faster scraping.

## Logging

Uses Serilog with:
- Console output
- Rolling file logs (daily)
- Structured JSON format

Log location: `{date}.json` in execution directory

## Maintenance Notes

1. **Selectors may change** - profixio.com updates may break CSS selectors
2. **Rate limiting** - Task.Delay() calls prevent overwhelming the server
3. **Session management** - Browser sessions can timeout during long scrapes
4. **Memory usage** - Long-running scrapes may accumulate memory
5. **Error recovery** - Retry logic handles transient failures

## Data Flow Summary

```
profixio.com
    │
    ▼ (PuppeteerSharp navigates & clicks)
Browser Page
    │
    ▼ (JavaScript executed in page context)
Extracted Data
    │
    ▼ (Serialized to JSON)
SendToApiService
    │
    ▼ (HTTP POST)
iRacket API
    │
    ▼
Database
```

## API Integration

The scraped data is sent to iRacket's API endpoints as form-encoded JSON:

```
POST {Host}/{Action}
Content-Type: application/x-www-form-urlencoded

data={serialized_json}
```

The iRacket Laravel application then processes this data to populate:
- Player profiles
- Club memberships
- Match history
- Rankings
- Statistics
