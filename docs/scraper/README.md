# WebScraper

In Order to create a version from the code you will need to navigate to the project with the command line and run this command 
```
dotnet publish -c release -r ubuntu.16.04-x64 --self-contained

```
If you want to change the version of the build you will need to replace <pre>ubuntu.16.04-x64</pre> with the version you need.


If you need to make any changes you will need to make changes into the <b>Shared/Scripts</b> or  <b>Shared/JavascriptHelpers.cs</b>

There selector maby will need to be changed.

Also added Rank Details which will be executed in parallel in order to speed up the process.
This will take a lot of CPU usage in order to speed up the process.
