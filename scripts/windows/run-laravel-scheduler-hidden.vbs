Option Explicit

Dim shell, fileSystem, phpPath, projectPath, logPath, command

If WScript.Arguments.Count < 2 Then
    WScript.Quit 2
End If

phpPath = WScript.Arguments(0)
projectPath = WScript.Arguments(1)
Set shell = CreateObject("WScript.Shell")
Set fileSystem = CreateObject("Scripting.FileSystemObject")
logPath = fileSystem.BuildPath(projectPath, "storage\logs\schedule-run.log")
shell.CurrentDirectory = projectPath

command = """" & phpPath & """ artisan schedule:run >> """ & logPath & """ 2>&1"
WScript.Quit shell.Run(command, 0, True)
