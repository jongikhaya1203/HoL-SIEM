; Networkscanscada (IOC Intelligent Operating Centre) Installer Script
; For Inno Setup 6.x - Download from https://jrsoftware.org/isinfo.php

#define MyAppName "IOC Intelligent Operating Centre"
#define MyAppVersion "1.0.0"
#define MyAppPublisher "Your Company Name"
#define MyAppURL "https://yourcompany.com"
#define MyAppExeName "start_ioc.bat"

[Setup]
; Application info
AppId={{A1B2C3D4-E5F6-7890-ABCD-EF1234567890}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppVerName={#MyAppName} {#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}

; Installation directories
DefaultDirName={autopf}\IOC
DefaultGroupName={#MyAppName}
AllowNoIcons=yes

; Output settings
OutputDir=output
OutputBaseFilename=IOC_Setup_{#MyAppVersion}
SetupIconFile=..\assets\icon.ico
Compression=lzma2/ultra64
SolidCompression=yes

; Privileges
PrivilegesRequired=admin
PrivilegesRequiredOverridesAllowed=dialog

; UI Settings
WizardStyle=modern
WizardResizable=no
DisableWelcomePage=no
LicenseFile=..\LICENSE.txt

; Uninstaller
UninstallDisplayIcon={app}\assets\icon.ico
UninstallDisplayName={#MyAppName}

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Types]
Name: "full"; Description: "Full installation (includes XAMPP portable)"
Name: "app_only"; Description: "Application only (requires existing XAMPP)"
Name: "custom"; Description: "Custom installation"; Flags: iscustom

[Components]
Name: "main"; Description: "IOC Application Files"; Types: full app_only custom; Flags: fixed
Name: "database"; Description: "Database Schema Files"; Types: full app_only custom
Name: "docs"; Description: "Documentation"; Types: full app_only custom
Name: "xampp"; Description: "XAMPP Portable (Apache + PHP + MySQL)"; Types: full

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked
Name: "quicklaunchicon"; Description: "{cm:CreateQuickLaunchIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked; OnlyBelowVersion: 6.1
Name: "createdb"; Description: "Create database and import schema"; GroupDescription: "Database Setup:"
Name: "startservices"; Description: "Start Apache and MySQL after installation"; GroupDescription: "Services:"

[Files]
; Main application files
Source: "..\*.php"; DestDir: "{app}"; Flags: ignoreversion; Components: main
Source: "..\*.md"; DestDir: "{app}"; Flags: ignoreversion; Components: docs
Source: "..\*.bat"; DestDir: "{app}"; Flags: ignoreversion; Components: main
Source: "..\*.txt"; DestDir: "{app}"; Flags: ignoreversion skipifsourcedoesntexist; Components: main

; Configuration
Source: "..\config\*"; DestDir: "{app}\config"; Flags: ignoreversion recursesubdirs createallsubdirs; Components: main

; Classes
Source: "..\classes\*"; DestDir: "{app}\classes"; Flags: ignoreversion recursesubdirs createallsubdirs; Components: main

; Modules
Source: "..\modules\*"; DestDir: "{app}\modules"; Flags: ignoreversion recursesubdirs createallsubdirs; Components: main

; Admin portal
Source: "..\admin\*"; DestDir: "{app}\admin"; Flags: ignoreversion recursesubdirs createallsubdirs; Components: main

; Database schemas
Source: "..\database\*"; DestDir: "{app}\database"; Flags: ignoreversion recursesubdirs createallsubdirs; Components: database

; Templates
Source: "..\templates\*"; DestDir: "{app}\templates"; Flags: ignoreversion recursesubdirs createallsubdirs skipifsourcedoesntexist; Components: main

; Assets
Source: "..\assets\*"; DestDir: "{app}\assets"; Flags: ignoreversion recursesubdirs createallsubdirs skipifsourcedoesntexist; Components: main

; Helper scripts
Source: "scripts\*"; DestDir: "{app}\scripts"; Flags: ignoreversion recursesubdirs createallsubdirs; Components: main

; XAMPP Portable (if full installation selected)
; Note: You need to download XAMPP portable and extract it to installer\xampp folder
Source: "xampp\*"; DestDir: "{app}\xampp"; Flags: ignoreversion recursesubdirs createallsubdirs; Components: xampp

[Dirs]
Name: "{app}\assets\uploads"; Permissions: users-modify
Name: "{app}\reports"; Permissions: users-modify
Name: "{app}\logs"; Permissions: users-modify

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; WorkingDir: "{app}"
Name: "{group}\{#MyAppName} Admin Panel"; Filename: "{app}\scripts\open_admin.bat"; WorkingDir: "{app}"
Name: "{group}\Start Services"; Filename: "{app}\scripts\start_services.bat"; WorkingDir: "{app}"
Name: "{group}\Stop Services"; Filename: "{app}\scripts\stop_services.bat"; WorkingDir: "{app}"
Name: "{group}\{cm:UninstallProgram,{#MyAppName}}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: desktopicon; WorkingDir: "{app}"

[Run]
; Post-installation scripts
Filename: "{app}\scripts\configure.bat"; Parameters: """{app}"""; StatusMsg: "Configuring application..."; Flags: runhidden waituntilterminated
Filename: "{app}\scripts\setup_database.bat"; Parameters: """{app}"""; StatusMsg: "Setting up database..."; Flags: runhidden waituntilterminated; Tasks: createdb
Filename: "{app}\scripts\start_services.bat"; Parameters: """{app}"""; StatusMsg: "Starting services..."; Flags: runhidden nowait; Tasks: startservices
Filename: "{app}\{#MyAppExeName}"; Description: "{cm:LaunchProgram,{#StringChange(MyAppName, '&', '&&')}}"; Flags: nowait postinstall skipifsilent shellexec

[UninstallRun]
Filename: "{app}\scripts\stop_services.bat"; Parameters: """{app}"""; Flags: runhidden waituntilterminated

[Code]
var
  XAMPPPage: TInputDirWizardPage;
  DBPage: TInputQueryWizardPage;

procedure InitializeWizard;
begin
  // Custom page for existing XAMPP location (if app_only selected)
  XAMPPPage := CreateInputDirPage(wpSelectComponents,
    'XAMPP Location',
    'Where is XAMPP installed?',
    'Select the folder where XAMPP is installed, then click Next.',
    False, '');
  XAMPPPage.Add('');
  XAMPPPage.Values[0] := 'C:\xampp';

  // Database configuration page
  DBPage := CreateInputQueryPage(XAMPPPage.ID,
    'Database Configuration',
    'Configure MySQL database settings',
    'Enter the MySQL database credentials:');
  DBPage.Add('Database Host:', False);
  DBPage.Add('Database Port:', False);
  DBPage.Add('Database Name:', False);
  DBPage.Add('Database User:', False);
  DBPage.Add('Database Password:', True);

  DBPage.Values[0] := 'localhost';
  DBPage.Values[1] := '3306';
  DBPage.Values[2] := 'networkscan';
  DBPage.Values[3] := 'root';
  DBPage.Values[4] := '';
end;

function ShouldSkipPage(PageID: Integer): Boolean;
begin
  Result := False;

  // Skip XAMPP page if full installation (includes XAMPP)
  if PageID = XAMPPPage.ID then
    Result := WizardIsComponentSelected('xampp');
end;

procedure SaveConfig;
var
  ConfigFile: string;
  ConfigContent: TStringList;
begin
  ConfigFile := ExpandConstant('{app}\config\database.php');
  ConfigContent := TStringList.Create;
  try
    ConfigContent.Add('<?php');
    ConfigContent.Add('// Database Configuration - Generated by Installer');
    ConfigContent.Add('');
    ConfigContent.Add('return [');
    ConfigContent.Add('    ''host'' => ''' + DBPage.Values[0] + ''',');
    ConfigContent.Add('    ''port'' => ' + DBPage.Values[1] + ',');
    ConfigContent.Add('    ''database'' => ''' + DBPage.Values[2] + ''',');
    ConfigContent.Add('    ''username'' => ''' + DBPage.Values[3] + ''',');
    ConfigContent.Add('    ''password'' => ''' + DBPage.Values[4] + ''',');
    ConfigContent.Add('    ''charset'' => ''utf8mb4'',');
    ConfigContent.Add('];');
    ConfigContent.SaveToFile(ConfigFile);
  finally
    ConfigContent.Free;
  end;

  // Save XAMPP path for scripts
  if not WizardIsComponentSelected('xampp') then
  begin
    SaveStringToFile(ExpandConstant('{app}\scripts\xampp_path.txt'), XAMPPPage.Values[0], False);
  end
  else
  begin
    SaveStringToFile(ExpandConstant('{app}\scripts\xampp_path.txt'), ExpandConstant('{app}\xampp'), False);
  end;
end;

procedure CurStepChanged(CurStep: TSetupStep);
begin
  if CurStep = ssPostInstall then
    SaveConfig;
end;

function GetXAMPPPath(Param: string): string;
begin
  if WizardIsComponentSelected('xampp') then
    Result := ExpandConstant('{app}\xampp')
  else
    Result := XAMPPPage.Values[0];
end;
