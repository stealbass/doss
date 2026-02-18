import 'package:flutter/material.dart';
import 'app_fr.dart';
import 'app_en.dart';

class AppLocalizations {
  final Locale locale;

  AppLocalizations(this.locale);

  static AppLocalizations? of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations);
  }

  static const LocalizationsDelegate<AppLocalizations> delegate = _AppLocalizationsDelegate();

  static const List<Locale> supportedLocales = [
    Locale('fr', 'FR'),
    Locale('en', 'US'),
  ];

  // General
  String get appName => locale.languageCode == 'en' ? AppLocalizations_en.appName : AppLocalizations_fr.appName;
  String get welcome => locale.languageCode == 'en' ? AppLocalizations_en.welcome : AppLocalizations_fr.welcome;
  String get loading => locale.languageCode == 'en' ? AppLocalizations_en.loading : AppLocalizations_fr.loading;
  String get error => locale.languageCode == 'en' ? AppLocalizations_en.error : AppLocalizations_fr.error;
  String get retry => locale.languageCode == 'en' ? AppLocalizations_en.retry : AppLocalizations_fr.retry;
  String get cancel => locale.languageCode == 'en' ? AppLocalizations_en.cancel : AppLocalizations_fr.cancel;
  String get save => locale.languageCode == 'en' ? AppLocalizations_en.save : AppLocalizations_fr.save;
  String get delete => locale.languageCode == 'en' ? AppLocalizations_en.delete : AppLocalizations_fr.delete;
  String get edit => locale.languageCode == 'en' ? AppLocalizations_en.edit : AppLocalizations_fr.edit;
  String get create => locale.languageCode == 'en' ? AppLocalizations_en.create : AppLocalizations_fr.create;
  String get search => locale.languageCode == 'en' ? AppLocalizations_en.search : AppLocalizations_fr.search;
  String get filter => locale.languageCode == 'en' ? AppLocalizations_en.filter : AppLocalizations_fr.filter;
  String get apply => locale.languageCode == 'en' ? AppLocalizations_en.apply : AppLocalizations_fr.apply;
  String get reset => locale.languageCode == 'en' ? AppLocalizations_en.reset : AppLocalizations_fr.reset;
  String get close => locale.languageCode == 'en' ? AppLocalizations_en.close : AppLocalizations_fr.close;
  String get yes => locale.languageCode == 'en' ? AppLocalizations_en.yes : AppLocalizations_fr.yes;
  String get no => locale.languageCode == 'en' ? AppLocalizations_en.no : AppLocalizations_fr.no;
  String get ok => locale.languageCode == 'en' ? AppLocalizations_en.ok : AppLocalizations_fr.ok;
  String get back => locale.languageCode == 'en' ? AppLocalizations_en.back : AppLocalizations_fr.back;
  String get next => locale.languageCode == 'en' ? AppLocalizations_en.next : AppLocalizations_fr.next;
  String get previous => locale.languageCode == 'en' ? AppLocalizations_en.previous : AppLocalizations_fr.previous;
  String get finish => locale.languageCode == 'en' ? AppLocalizations_en.finish : AppLocalizations_fr.finish;
  String get share => locale.languageCode == 'en' ? AppLocalizations_en.share : AppLocalizations_fr.share;
  String get download => locale.languageCode == 'en' ? AppLocalizations_en.download : AppLocalizations_fr.download;
  String get refresh => locale.languageCode == 'en' ? AppLocalizations_en.refresh : AppLocalizations_fr.refresh;
  String get upload => locale.languageCode == 'en' ? AppLocalizations_en.upload : AppLocalizations_fr.upload;
  String get noData => locale.languageCode == 'en' ? AppLocalizations_en.noData : AppLocalizations_fr.noData;
  String get noResults => locale.languageCode == 'en' ? AppLocalizations_en.noResults : AppLocalizations_fr.noResults;

  // Authentication
  String get login => locale.languageCode == 'en' ? AppLocalizations_en.login : AppLocalizations_fr.login;
  String get register => locale.languageCode == 'en' ? AppLocalizations_en.register : AppLocalizations_fr.register;
  String get logout => locale.languageCode == 'en' ? AppLocalizations_en.logout : AppLocalizations_fr.logout;
  String get email => locale.languageCode == 'en' ? AppLocalizations_en.email : AppLocalizations_fr.email;
  String get password => locale.languageCode == 'en' ? AppLocalizations_en.password : AppLocalizations_fr.password;
  String get confirmPassword => locale.languageCode == 'en' ? AppLocalizations_en.confirmPassword : AppLocalizations_fr.confirmPassword;
  String get forgotPassword => locale.languageCode == 'en' ? AppLocalizations_en.forgotPassword : AppLocalizations_fr.forgotPassword;
  String get resetPassword => locale.languageCode == 'en' ? AppLocalizations_en.resetPassword : AppLocalizations_fr.resetPassword;
  String get name => locale.languageCode == 'en' ? AppLocalizations_en.name : AppLocalizations_fr.name;
  String get phone => locale.languageCode == 'en' ? AppLocalizations_en.phone : AppLocalizations_fr.phone;
  String get country => locale.languageCode == 'en' ? AppLocalizations_en.country : AppLocalizations_fr.country;
  String get selectCountry => locale.languageCode == 'en' ? AppLocalizations_en.selectCountry : AppLocalizations_fr.selectCountry;

  // Navigation
  String get home => locale.languageCode == 'en' ? AppLocalizations_en.home : AppLocalizations_fr.home;
  String get chat => locale.languageCode == 'en' ? AppLocalizations_en.chat : AppLocalizations_fr.chat;
  String get documents => locale.languageCode == 'en' ? AppLocalizations_en.documents : AppLocalizations_fr.documents;
  String get templates => locale.languageCode == 'en' ? AppLocalizations_en.templates : AppLocalizations_fr.templates;
  String get calculators => locale.languageCode == 'en' ? AppLocalizations_en.calculators : AppLocalizations_fr.calculators;
  String get fiscalResources => locale.languageCode == 'en' ? AppLocalizations_en.fiscalResources : AppLocalizations_fr.fiscalResources;
  String get legalAlerts => locale.languageCode == 'en' ? AppLocalizations_en.legalAlerts : AppLocalizations_fr.legalAlerts;
  String get enterprise => locale.languageCode == 'en' ? AppLocalizations_en.enterprise : AppLocalizations_fr.enterprise;
  String get profile => locale.languageCode == 'en' ? AppLocalizations_en.profile : AppLocalizations_fr.profile;
  String get settings => locale.languageCode == 'en' ? AppLocalizations_en.settings : AppLocalizations_fr.settings;
  String get subscription => locale.languageCode == 'en' ? AppLocalizations_en.subscription : AppLocalizations_fr.subscription;

  // Templates
  String get documentTemplates => locale.languageCode == 'en' ? AppLocalizations_en.documentTemplates : AppLocalizations_fr.documentTemplates;
  String get templatesList => locale.languageCode == 'en' ? AppLocalizations_en.templatesList : AppLocalizations_fr.templatesList;
  String get templateDetails => locale.languageCode == 'en' ? AppLocalizations_en.templateDetails : AppLocalizations_fr.templateDetails;
  String get downloadTemplate => locale.languageCode == 'en' ? AppLocalizations_en.downloadTemplate : AppLocalizations_fr.downloadTemplate;
  // 'downloading' getter already declared earlier; removed duplicate here
  String get searchTemplates => locale.languageCode == 'en' ? AppLocalizations_en.searchTemplates : AppLocalizations_fr.searchTemplates;
  String get noTemplates => locale.languageCode == 'en' ? AppLocalizations_en.noTemplates : AppLocalizations_fr.noTemplates;
  String get views => locale.languageCode == 'en' ? AppLocalizations_en.views : AppLocalizations_fr.views;
  // 'downloads' already declared earlier; removed duplicate here
  String get usageInstructions => locale.languageCode == 'en' ? AppLocalizations_en.usageInstructions : AppLocalizations_fr.usageInstructions;
  String get useButtonToDownload => locale.languageCode == 'en' ? AppLocalizations_en.useButtonToDownload : AppLocalizations_fr.useButtonToDownload;
  String get fillVariables => locale.languageCode == 'en' ? AppLocalizations_en.fillVariables : AppLocalizations_fr.fillVariables;
  String get replaceVariables => locale.languageCode == 'en' ? AppLocalizations_en.replaceVariables : AppLocalizations_fr.replaceVariables;
  String get customizeIfNeeded => locale.languageCode == 'en' ? AppLocalizations_en.customizeIfNeeded : AppLocalizations_fr.customizeIfNeeded;
  String get adaptContent => locale.languageCode == 'en' ? AppLocalizations_en.adaptContent : AppLocalizations_fr.adaptContent;
  String get tags => locale.languageCode == 'en' ? AppLocalizations_en.tags : AppLocalizations_fr.tags;
  String get supportedCountries => locale.languageCode == 'en' ? AppLocalizations_en.supportedCountries : AppLocalizations_fr.supportedCountries;

  // Calculators
  String get calculatorsSimulators => locale.languageCode == 'en' ? AppLocalizations_en.calculatorsSimulators : AppLocalizations_fr.calculatorsSimulators;
  String get calculatorsList => locale.languageCode == 'en' ? AppLocalizations_en.calculatorsList : AppLocalizations_fr.calculatorsList;
  String get calculate => locale.languageCode == 'en' ? AppLocalizations_en.calculate : AppLocalizations_fr.calculate;
  String get calculating => locale.languageCode == 'en' ? AppLocalizations_en.calculating : AppLocalizations_fr.calculating;
  String get result => locale.languageCode == 'en' ? AppLocalizations_en.result : AppLocalizations_fr.result;
  String get results => locale.languageCode == 'en' ? AppLocalizations_en.results : AppLocalizations_fr.results;
  String get calculation => locale.languageCode == 'en' ? AppLocalizations_en.calculation : AppLocalizations_fr.calculation;
  String get calculationHistory => locale.languageCode == 'en' ? AppLocalizations_en.calculationHistory : AppLocalizations_fr.calculationHistory;
  String get newCalculation => locale.languageCode == 'en' ? AppLocalizations_en.newCalculation : AppLocalizations_fr.newCalculation;
  String get enterInformation => locale.languageCode == 'en' ? AppLocalizations_en.enterInformation : AppLocalizations_fr.enterInformation;
  String get calculationDetails => locale.languageCode == 'en' ? AppLocalizations_en.calculationDetails : AppLocalizations_fr.calculationDetails;
  String get breakdown => locale.languageCode == 'en' ? AppLocalizations_en.breakdown : AppLocalizations_fr.breakdown;
  String get mainResult => locale.languageCode == 'en' ? AppLocalizations_en.mainResult : AppLocalizations_fr.mainResult;
  String get usedData => locale.languageCode == 'en' ? AppLocalizations_en.usedData : AppLocalizations_fr.usedData;
  String get dataUsed => locale.languageCode == 'en' ? AppLocalizations_en.dataUsed : AppLocalizations_fr.dataUsed;
  String get legalNotice => locale.languageCode == 'en' ? AppLocalizations_en.legalNotice : AppLocalizations_fr.legalNotice;
  String get importantNotes => locale.languageCode == 'en' ? AppLocalizations_en.importantNotes : AppLocalizations_fr.importantNotes;
  String get saveToHistory => locale.languageCode == 'en' ? AppLocalizations_en.saveToHistory : AppLocalizations_fr.saveToHistory;
  String get savedSuccessfully => locale.languageCode == 'en' ? AppLocalizations_en.savedSuccessfully : AppLocalizations_fr.savedSuccessfully;
  String get calculationSuccess => locale.languageCode == 'en' ? AppLocalizations_en.calculationSuccess : AppLocalizations_fr.calculationSuccess;
  String get calculationError => locale.languageCode == 'en' ? AppLocalizations_en.calculationError : AppLocalizations_fr.calculationError;
  String get requiredField => locale.languageCode == 'en' ? AppLocalizations_en.requiredField : AppLocalizations_fr.requiredField;
  String get invalidNumber => locale.languageCode == 'en' ? AppLocalizations_en.invalidNumber : AppLocalizations_fr.invalidNumber;

  // Fiscal Resources
  String get fiscalSocialResources => locale.languageCode == 'en' ? AppLocalizations_en.fiscalSocialResources : AppLocalizations_fr.fiscalSocialResources;
  String get resourcesList => locale.languageCode == 'en' ? AppLocalizations_en.resourcesList : AppLocalizations_fr.resourcesList;
  String get resourceDetails => locale.languageCode == 'en' ? AppLocalizations_en.resourceDetails : AppLocalizations_fr.resourceDetails;
  String get searchResources => locale.languageCode == 'en' ? AppLocalizations_en.searchResources : AppLocalizations_fr.searchResources;
  String get noResources => locale.languageCode == 'en' ? AppLocalizations_en.noResources : AppLocalizations_fr.noResources;
  String get resourceType => locale.languageCode == 'en' ? AppLocalizations_en.resourceType : AppLocalizations_fr.resourceType;
  String get year => locale.languageCode == 'en' ? AppLocalizations_en.year : AppLocalizations_fr.year;
  String get applicableYear => locale.languageCode == 'en' ? AppLocalizations_en.applicableYear : AppLocalizations_fr.applicableYear;
  String get version => locale.languageCode == 'en' ? AppLocalizations_en.version : AppLocalizations_fr.version;
  String get resourceContent => locale.languageCode == 'en' ? AppLocalizations_en.resourceContent : AppLocalizations_fr.resourceContent;
  String get legalReferences => locale.languageCode == 'en' ? AppLocalizations_en.legalReferences : AppLocalizations_fr.legalReferences;
  String get usageGuide => locale.languageCode == 'en' ? AppLocalizations_en.usageGuide : AppLocalizations_fr.usageGuide;
  String get consult => locale.languageCode == 'en' ? AppLocalizations_en.consult : AppLocalizations_fr.consult;
  String get readFiscalInfo => locale.languageCode == 'en' ? AppLocalizations_en.readFiscalInfo : AppLocalizations_fr.readFiscalInfo;
  String get downloadDocument => locale.languageCode == 'en' ? AppLocalizations_en.downloadDocument : AppLocalizations_fr.downloadDocument;
  String get useForDeclarations => locale.languageCode == 'en' ? AppLocalizations_en.useForDeclarations : AppLocalizations_fr.useForDeclarations;
  String get declarationsInfo => locale.languageCode == 'en' ? AppLocalizations_en.declarationsInfo : AppLocalizations_fr.declarationsInfo;
  String get scalesInfo => locale.languageCode == 'en' ? AppLocalizations_en.scalesInfo : AppLocalizations_fr.scalesInfo;
  String get formsInfo => locale.languageCode == 'en' ? AppLocalizations_en.formsInfo : AppLocalizations_fr.formsInfo;

  // Legal Alerts
  String get legalAlertsTitle => locale.languageCode == 'en' ? AppLocalizations_en.legalAlertsTitle : AppLocalizations_fr.legalAlertsTitle;
  String get alertsList => locale.languageCode == 'en' ? AppLocalizations_en.alertsList : AppLocalizations_fr.alertsList;
  String get alertDetails => locale.languageCode == 'en' ? AppLocalizations_en.alertDetails : AppLocalizations_fr.alertDetails;
  String get unreadAlerts => locale.languageCode == 'en' ? AppLocalizations_en.unreadAlerts : AppLocalizations_fr.unreadAlerts;
  String get unread => locale.languageCode == 'en' ? AppLocalizations_en.unread : AppLocalizations_fr.unread;
  String get markAsRead => locale.languageCode == 'en' ? AppLocalizations_en.markAsRead : AppLocalizations_fr.markAsRead;
  String get noAlerts => locale.languageCode == 'en' ? AppLocalizations_en.noAlerts : AppLocalizations_fr.noAlerts;

  // Enterprise
  String get enterpriseDashboard => locale.languageCode == 'en' ? AppLocalizations_en.enterpriseDashboard : AppLocalizations_fr.enterpriseDashboard;
  String get subAccounts => locale.languageCode == 'en' ? AppLocalizations_en.subAccounts : AppLocalizations_fr.subAccounts;
  String get createSubAccount => locale.languageCode == 'en' ? AppLocalizations_en.createSubAccount : AppLocalizations_fr.createSubAccount;
  String get subAccountDetails => locale.languageCode == 'en' ? AppLocalizations_en.subAccountDetails : AppLocalizations_fr.subAccountDetails;
  String get totalSubAccounts => locale.languageCode == 'en' ? AppLocalizations_en.totalSubAccounts : AppLocalizations_fr.totalSubAccounts;
  String get activeSubAccounts => locale.languageCode == 'en' ? AppLocalizations_en.activeSubAccounts : AppLocalizations_fr.activeSubAccounts;
  String get quotaUsage => locale.languageCode == 'en' ? AppLocalizations_en.quotaUsage : AppLocalizations_fr.quotaUsage;
  String get recentActivity => locale.languageCode == 'en' ? AppLocalizations_en.recentActivity : AppLocalizations_fr.recentActivity;
  String get manageSubAccounts => locale.languageCode == 'en' ? AppLocalizations_en.manageSubAccounts : AppLocalizations_fr.manageSubAccounts;
  String get fullName => locale.languageCode == 'en' ? AppLocalizations_en.fullName : AppLocalizations_fr.fullName;
  String get department => locale.languageCode == 'en' ? AppLocalizations_en.department : AppLocalizations_fr.department;
  String get departmentExample => locale.languageCode == 'en' ? AppLocalizations_en.departmentExample : AppLocalizations_fr.departmentExample;
  String get role => locale.languageCode == 'en' ? AppLocalizations_en.role : AppLocalizations_fr.role;
  String get roleAndPermissions => locale.languageCode == 'en' ? AppLocalizations_en.roleAndPermissions : AppLocalizations_fr.roleAndPermissions;
  String get rolePermissions => locale.languageCode == 'en' ? AppLocalizations_en.rolePermissions : AppLocalizations_fr.rolePermissions;
  String get administrator => locale.languageCode == 'en' ? AppLocalizations_en.administrator : AppLocalizations_fr.administrator;
  String get manager => locale.languageCode == 'en' ? AppLocalizations_en.manager : AppLocalizations_fr.manager;
  String get accountant => locale.languageCode == 'en' ? AppLocalizations_en.accountant : AppLocalizations_fr.accountant;
  String get hr => locale.languageCode == 'en' ? AppLocalizations_en.hr : AppLocalizations_fr.hr;
  String get viewer => locale.languageCode == 'en' ? AppLocalizations_en.viewer : AppLocalizations_fr.viewer;
  String get fullAccessFeatures => locale.languageCode == 'en' ? AppLocalizations_en.fullAccessFeatures : AppLocalizations_fr.fullAccessFeatures;
  String get manageEmployees => locale.languageCode == 'en' ? AppLocalizations_en.manageEmployees : AppLocalizations_fr.manageEmployees;
  String get accessCalculators => locale.languageCode == 'en' ? AppLocalizations_en.accessCalculators : AppLocalizations_fr.accessCalculators;
  String get manageTemplates => locale.languageCode == 'en' ? AppLocalizations_en.manageTemplates : AppLocalizations_fr.manageTemplates;
  String get consultOnly => locale.languageCode == 'en' ? AppLocalizations_en.consultOnly : AppLocalizations_fr.consultOnly;
  String get createSubAccountTeam => locale.languageCode == 'en' ? AppLocalizations_en.createSubAccountTeam : AppLocalizations_fr.createSubAccountTeam;
  String get createAccount => locale.languageCode == 'en' ? AppLocalizations_en.createAccount : AppLocalizations_fr.createAccount;
  String get creating => locale.languageCode == 'en' ? AppLocalizations_en.creating : AppLocalizations_fr.creating;
  String get subAccountCreated => locale.languageCode == 'en' ? AppLocalizations_en.subAccountCreated : AppLocalizations_fr.subAccountCreated;
  String get invalidEmail => locale.languageCode == 'en' ? AppLocalizations_en.invalidEmail : AppLocalizations_fr.invalidEmail;

  // Subscription Plans
  String get subscriptionPlans => locale.languageCode == 'en' ? AppLocalizations_en.subscriptionPlans : AppLocalizations_fr.subscriptionPlans;
  String get currentPlan => locale.languageCode == 'en' ? AppLocalizations_en.currentPlan : AppLocalizations_fr.currentPlan;
  String get upgradePlan => locale.languageCode == 'en' ? AppLocalizations_en.upgradePlan : AppLocalizations_fr.upgradePlan;
  String get freePlan => locale.languageCode == 'en' ? AppLocalizations_en.freePlan : AppLocalizations_fr.freePlan;
  String get studentPlan => locale.languageCode == 'en' ? AppLocalizations_en.studentPlan : AppLocalizations_fr.studentPlan;
  String get professionalPlan => locale.languageCode == 'en' ? AppLocalizations_en.professionalPlan : AppLocalizations_fr.professionalPlan;
  String get enterprisePlan => locale.languageCode == 'en' ? AppLocalizations_en.enterprisePlan : AppLocalizations_fr.enterprisePlan;
  String get subscribe => locale.languageCode == 'en' ? AppLocalizations_en.subscribe : AppLocalizations_fr.subscribe;
  // 'features' already declared earlier; removed duplicate here
  String get unlimited => locale.languageCode == 'en' ? AppLocalizations_en.unlimited : AppLocalizations_fr.unlimited;

  // Errors
  String get errorOccurred => locale.languageCode == 'en' ? AppLocalizations_en.errorOccurred : AppLocalizations_fr.errorOccurred;
  String get tryAgain => locale.languageCode == 'en' ? AppLocalizations_en.tryAgain : AppLocalizations_fr.tryAgain;
  String get networkError => locale.languageCode == 'en' ? AppLocalizations_en.networkError : AppLocalizations_fr.networkError;
  String get serverError => locale.languageCode == 'en' ? AppLocalizations_en.serverError : AppLocalizations_fr.serverError;
  String get notFound => locale.languageCode == 'en' ? AppLocalizations_en.notFound : AppLocalizations_fr.notFound;
  String get accessDenied => locale.languageCode == 'en' ? AppLocalizations_en.accessDenied : AppLocalizations_fr.accessDenied;
  String get upgradeRequired => locale.languageCode == 'en' ? AppLocalizations_en.upgradeRequired : AppLocalizations_fr.upgradeRequired;
  String get featureNotAvailable => locale.languageCode == 'en' ? AppLocalizations_en.featureNotAvailable : AppLocalizations_fr.featureNotAvailable;

  // Common
  String get description => locale.languageCode == 'en' ? AppLocalizations_en.description : AppLocalizations_fr.description;
  String get title => locale.languageCode == 'en' ? AppLocalizations_en.title : AppLocalizations_fr.title;
  String get type => locale.languageCode == 'en' ? AppLocalizations_en.type : AppLocalizations_fr.type;
  String get status => locale.languageCode == 'en' ? AppLocalizations_en.status : AppLocalizations_fr.status;
  String get active => locale.languageCode == 'en' ? AppLocalizations_en.active : AppLocalizations_fr.active;
  String get inactive => locale.languageCode == 'en' ? AppLocalizations_en.inactive : AppLocalizations_fr.inactive;
  String get all => locale.languageCode == 'en' ? AppLocalizations_en.all : AppLocalizations_fr.all;
  String get filters => locale.languageCode == 'en' ? AppLocalizations_en.filters : AppLocalizations_fr.filters;
  String get applyFilters => locale.languageCode == 'en' ? AppLocalizations_en.applyFilters : AppLocalizations_fr.applyFilters;
  
  // Profile Screen
  String get myProfile => locale.languageCode == 'en' ? AppLocalizations_en.myProfile : AppLocalizations_fr.myProfile;
  String get personalInformation => locale.languageCode == 'en' ? AppLocalizations_en.personalInformation : AppLocalizations_fr.personalInformation;
  // String get fullName => locale.languageCode == 'en' ? AppLocalizations_en.fullName : AppLocalizations_fr.fullName; // DUPLICATE - removed
  String get address => locale.languageCode == 'en' ? AppLocalizations_en.address : AppLocalizations_fr.address;
  String get city => locale.languageCode == 'en' ? AppLocalizations_en.city : AppLocalizations_fr.city;
  String get phoneNumber => locale.languageCode == 'en' ? AppLocalizations_en.phoneNumber : AppLocalizations_fr.phoneNumber;
  String get emailAddress => locale.languageCode == 'en' ? AppLocalizations_en.emailAddress : AppLocalizations_fr.emailAddress;
  String get editProfile => locale.languageCode == 'en' ? AppLocalizations_en.editProfile : AppLocalizations_fr.editProfile;
  String get saveChanges => locale.languageCode == 'en' ? AppLocalizations_en.saveChanges : AppLocalizations_fr.saveChanges;
  String get preferences => locale.languageCode == 'en' ? AppLocalizations_en.preferences : AppLocalizations_fr.preferences;
  String get darkMode => locale.languageCode == 'en' ? AppLocalizations_en.darkMode : AppLocalizations_fr.darkMode;
  String get enableDarkTheme => locale.languageCode == 'en' ? AppLocalizations_en.enableDarkTheme : AppLocalizations_fr.enableDarkTheme;
  String get language => locale.languageCode == 'en' ? AppLocalizations_en.language : AppLocalizations_fr.language;
  String get french => locale.languageCode == 'en' ? AppLocalizations_en.french : AppLocalizations_fr.french;
  String get english => locale.languageCode == 'en' ? AppLocalizations_en.english : AppLocalizations_fr.english;
  String get languageApplied => locale.languageCode == 'en' ? AppLocalizations_en.languageApplied : AppLocalizations_fr.languageApplied;
  String get subscriptionPlan => locale.languageCode == 'en' ? AppLocalizations_en.subscriptionPlan : AppLocalizations_fr.subscriptionPlan;
  String get searches => locale.languageCode == 'en' ? AppLocalizations_en.searches : AppLocalizations_fr.searches;
  String get analyses => locale.languageCode == 'en' ? AppLocalizations_en.analyses : AppLocalizations_fr.analyses;
  String get messagesPerDay => locale.languageCode == 'en' ? AppLocalizations_en.messagesPerDay : AppLocalizations_fr.messagesPerDay;
  String get downloads => locale.languageCode == 'en' ? AppLocalizations_en.downloads : AppLocalizations_fr.downloads;
  String get upgradeSubscription => locale.languageCode == 'en' ? AppLocalizations_en.upgradeSubscription : AppLocalizations_fr.upgradeSubscription;
  String get features => locale.languageCode == 'en' ? AppLocalizations_en.features : AppLocalizations_fr.features;
  String get expiresOn => locale.languageCode == 'en' ? AppLocalizations_en.expiresOn : AppLocalizations_fr.expiresOn;
  String get referralProgram => locale.languageCode == 'en' ? AppLocalizations_en.referralProgram : AppLocalizations_fr.referralProgram;
  String get earnRewards => locale.languageCode == 'en' ? AppLocalizations_en.earnRewards : AppLocalizations_fr.earnRewards;
  String get documentAnonymization => locale.languageCode == 'en' ? AppLocalizations_en.documentAnonymization : AppLocalizations_fr.documentAnonymization;
  String get dataProtection => locale.languageCode == 'en' ? AppLocalizations_en.dataProtection : AppLocalizations_fr.dataProtection;
  String get legalMonitoring => locale.languageCode == 'en' ? AppLocalizations_en.legalMonitoring : AppLocalizations_fr.legalMonitoring;
  String get newsAndAlerts => locale.languageCode == 'en' ? AppLocalizations_en.newsAndAlerts : AppLocalizations_fr.newsAndAlerts;
  String get support => locale.languageCode == 'en' ? AppLocalizations_en.support : AppLocalizations_fr.support;
  String get contactUs => locale.languageCode == 'en' ? AppLocalizations_en.contactUs : AppLocalizations_fr.contactUs;
  String get cannotOpenEmail => locale.languageCode == 'en' ? AppLocalizations_en.cannotOpenEmail : AppLocalizations_fr.cannotOpenEmail;
  String get logoutButton => locale.languageCode == 'en' ? AppLocalizations_en.logoutButton : AppLocalizations_fr.logoutButton;
  String get confirmLogout => locale.languageCode == 'en' ? AppLocalizations_en.confirmLogout : AppLocalizations_fr.confirmLogout;
  String get profileUpdatedSuccess => locale.languageCode == 'en' ? AppLocalizations_en.profileUpdatedSuccess : AppLocalizations_fr.profileUpdatedSuccess;
  String get updateError => locale.languageCode == 'en' ? AppLocalizations_en.updateError : AppLocalizations_fr.updateError;
  String get pleaseEnterName => locale.languageCode == 'en' ? AppLocalizations_en.pleaseEnterName : AppLocalizations_fr.pleaseEnterName;
  String get completeProfileTitle => locale.languageCode == 'en' ? AppLocalizations_en.completeProfileTitle : AppLocalizations_fr.completeProfileTitle;
  String get completeProfileMessage => locale.languageCode == 'en' ? AppLocalizations_en.completeProfileMessage : AppLocalizations_fr.completeProfileMessage;
  String get missingFieldsLabel => locale.languageCode == 'en' ? AppLocalizations_en.missingFieldsLabel : AppLocalizations_fr.missingFieldsLabel;
  String get completeNow => locale.languageCode == 'en' ? AppLocalizations_en.completeNow : AppLocalizations_fr.completeNow;
  String get later => locale.languageCode == 'en' ? AppLocalizations_en.later : AppLocalizations_fr.later;
  
  // Auth Screens
  String get loginSubtitle => locale.languageCode == 'en' ? AppLocalizations_en.loginSubtitle : AppLocalizations_fr.loginSubtitle;
  String get emailHint => locale.languageCode == 'en' ? AppLocalizations_en.emailHint : AppLocalizations_fr.emailHint;
  String get pleaseEnterEmail => locale.languageCode == 'en' ? AppLocalizations_en.pleaseEnterEmail : AppLocalizations_fr.pleaseEnterEmail;
  // String get invalidEmail => locale.languageCode == 'en' ? AppLocalizations_en.invalidEmail : AppLocalizations_fr.invalidEmail; // DUPLICATE - removed
  String get passwordHint => locale.languageCode == 'en' ? AppLocalizations_en.passwordHint : AppLocalizations_fr.passwordHint;
  String get pleaseEnterPassword => locale.languageCode == 'en' ? AppLocalizations_en.pleaseEnterPassword : AppLocalizations_fr.pleaseEnterPassword;
  String get passwordMinLength => locale.languageCode == 'en' ? AppLocalizations_en.passwordMinLength : AppLocalizations_fr.passwordMinLength;
  String get enterYourEmail => locale.languageCode == 'en' ? AppLocalizations_en.enterYourEmail : AppLocalizations_fr.enterYourEmail;
  String get send => locale.languageCode == 'en' ? AppLocalizations_en.send : AppLocalizations_fr.send;
  String get resetEmailSent => locale.languageCode == 'en' ? AppLocalizations_en.resetEmailSent : AppLocalizations_fr.resetEmailSent;
  String get requestError => locale.languageCode == 'en' ? AppLocalizations_en.requestError : AppLocalizations_fr.requestError;
  String get notAuthenticated => locale.languageCode == 'en' ? AppLocalizations_en.notAuthenticated : AppLocalizations_fr.notAuthenticated;
  String get downloadUrlNotAvailable => locale.languageCode == 'en' ? AppLocalizations_en.downloadUrlNotAvailable : AppLocalizations_fr.downloadUrlNotAvailable;
  String get forgotPasswordQuestion => locale.languageCode == 'en' ? AppLocalizations_en.forgotPasswordQuestion : AppLocalizations_fr.forgotPasswordQuestion;
  String get or => locale.languageCode == 'en' ? AppLocalizations_en.or : AppLocalizations_fr.or;
  String get noAccountYet => locale.languageCode == 'en' ? AppLocalizations_en.noAccountYet : AppLocalizations_fr.noAccountYet;
  String get signUp => locale.languageCode == 'en' ? AppLocalizations_en.signUp : AppLocalizations_fr.signUp;
  String get loginError => locale.languageCode == 'en' ? AppLocalizations_en.loginError : AppLocalizations_fr.loginError;
  // String get createAccount => locale.languageCode == 'en' ? AppLocalizations_en.createAccount : AppLocalizations_fr.createAccount; // DUPLICATE - removed
  String get joinDossyChat => locale.languageCode == 'en' ? AppLocalizations_en.joinDossyChat : AppLocalizations_fr.joinDossyChat;
  String get registerSubtitle => locale.languageCode == 'en' ? AppLocalizations_en.registerSubtitle : AppLocalizations_fr.registerSubtitle;
  String get fullNameRequired => locale.languageCode == 'en' ? AppLocalizations_en.fullNameRequired : AppLocalizations_fr.fullNameRequired;
  String get nameHint => locale.languageCode == 'en' ? AppLocalizations_en.nameHint : AppLocalizations_fr.nameHint;
  String get emailRequired => locale.languageCode == 'en' ? AppLocalizations_en.emailRequired : AppLocalizations_fr.emailRequired;
  String get phoneRequired => locale.languageCode == 'en' ? AppLocalizations_en.phoneRequired : AppLocalizations_fr.phoneRequired;
  String get phoneHint => locale.languageCode == 'en' ? AppLocalizations_en.phoneHint : AppLocalizations_fr.phoneHint;
  String get pleaseEnterPhone => locale.languageCode == 'en' ? AppLocalizations_en.pleaseEnterPhone : AppLocalizations_fr.pleaseEnterPhone;
  String get youAre => locale.languageCode == 'en' ? AppLocalizations_en.youAre : AppLocalizations_fr.youAre;
  String get roleStudent => locale.languageCode == 'en' ? AppLocalizations_en.roleStudent : AppLocalizations_fr.roleStudent;
  String get roleLawyer => locale.languageCode == 'en' ? AppLocalizations_en.roleLawyer : AppLocalizations_fr.roleLawyer;
  String get roleEnterprise => locale.languageCode == 'en' ? AppLocalizations_en.roleEnterprise : AppLocalizations_fr.roleEnterprise;
  String get selectYourProfile => locale.languageCode == 'en' ? AppLocalizations_en.selectYourProfile : AppLocalizations_fr.selectYourProfile;
  String get countryJurisdiction => locale.languageCode == 'en' ? AppLocalizations_en.countryJurisdiction : AppLocalizations_fr.countryJurisdiction;
  String get selectYourCountry => locale.languageCode == 'en' ? AppLocalizations_en.selectYourCountry : AppLocalizations_fr.selectYourCountry;
  String get passwordRequired => locale.languageCode == 'en' ? AppLocalizations_en.passwordRequired : AppLocalizations_fr.passwordRequired;
  String get passwordMinHint => locale.languageCode == 'en' ? AppLocalizations_en.passwordMinHint : AppLocalizations_fr.passwordMinHint;
  String get passwordMinLength8 => locale.languageCode == 'en' ? AppLocalizations_en.passwordMinLength8 : AppLocalizations_fr.passwordMinLength8;
  String get confirmPasswordRequired => locale.languageCode == 'en' ? AppLocalizations_en.confirmPasswordRequired : AppLocalizations_fr.confirmPasswordRequired;
  String get retypePassword => locale.languageCode == 'en' ? AppLocalizations_en.retypePassword : AppLocalizations_fr.retypePassword;
  String get pleaseConfirmPassword => locale.languageCode == 'en' ? AppLocalizations_en.pleaseConfirmPassword : AppLocalizations_fr.pleaseConfirmPassword;
  String get passwordsMismatch => locale.languageCode == 'en' ? AppLocalizations_en.passwordsMismatch : AppLocalizations_fr.passwordsMismatch;
  String get referralCodeOptional => locale.languageCode == 'en' ? AppLocalizations_en.referralCodeOptional : AppLocalizations_fr.referralCodeOptional;
  String get referralCodeHint => locale.languageCode == 'en' ? AppLocalizations_en.referralCodeHint : AppLocalizations_fr.referralCodeHint;
  String get iAcceptThe => locale.languageCode == 'en' ? AppLocalizations_en.iAcceptThe : AppLocalizations_fr.iAcceptThe;
  String get termsOfUse => locale.languageCode == 'en' ? AppLocalizations_en.termsOfUse : AppLocalizations_fr.termsOfUse;
  String get andThe => locale.languageCode == 'en' ? AppLocalizations_en.andThe : AppLocalizations_fr.andThe;
  String get privacyPolicy => locale.languageCode == 'en' ? AppLocalizations_en.privacyPolicy : AppLocalizations_fr.privacyPolicy;
  String get createMyAccount => locale.languageCode == 'en' ? AppLocalizations_en.createMyAccount : AppLocalizations_fr.createMyAccount;
  String get alreadyHaveAccountQuestion => locale.languageCode == 'en' ? AppLocalizations_en.alreadyHaveAccountQuestion : AppLocalizations_fr.alreadyHaveAccountQuestion;
  String get mustAcceptTerms => locale.languageCode == 'en' ? AppLocalizations_en.mustAcceptTerms : AppLocalizations_fr.mustAcceptTerms;
  String get registrationError => locale.languageCode == 'en' ? AppLocalizations_en.registrationError : AppLocalizations_fr.registrationError;
  
  // Onboarding
  String get onboardingTitle1 => locale.languageCode == 'en' ? AppLocalizations_en.onboardingTitle1 : AppLocalizations_fr.onboardingTitle1;
  String get onboardingDesc1 => locale.languageCode == 'en' ? AppLocalizations_en.onboardingDesc1 : AppLocalizations_fr.onboardingDesc1;
  String get onboardingTitle2 => locale.languageCode == 'en' ? AppLocalizations_en.onboardingTitle2 : AppLocalizations_fr.onboardingTitle2;
  String get onboardingDesc2 => locale.languageCode == 'en' ? AppLocalizations_en.onboardingDesc2 : AppLocalizations_fr.onboardingDesc2;
  String get onboardingTitle3 => locale.languageCode == 'en' ? AppLocalizations_en.onboardingTitle3 : AppLocalizations_fr.onboardingTitle3;
  String get onboardingDesc3 => locale.languageCode == 'en' ? AppLocalizations_en.onboardingDesc3 : AppLocalizations_fr.onboardingDesc3;
  String get onboardingTitle4 => locale.languageCode == 'en' ? AppLocalizations_en.onboardingTitle4 : AppLocalizations_fr.onboardingTitle4;
  String get onboardingDesc4 => locale.languageCode == 'en' ? AppLocalizations_en.onboardingDesc4 : AppLocalizations_fr.onboardingDesc4;
  String get skip => locale.languageCode == 'en' ? AppLocalizations_en.skip : AppLocalizations_fr.skip;
  String get getStarted => locale.languageCode == 'en' ? AppLocalizations_en.getStarted : AppLocalizations_fr.getStarted;
  
  // Navigation
  String get navChat {
    switch (locale.languageCode) {
      case 'en':
        return AppLocalizations_en.navChat;
      case 'fr':
      default:
        return AppLocalizations_fr.navChat;
    }
  }
  
  String get navDocuments {
    switch (locale.languageCode) {
      case 'en':
        return AppLocalizations_en.navDocuments;
      case 'fr':
      default:
        return AppLocalizations_fr.navDocuments;
    }
  }
  
  String get navTools {
    switch (locale.languageCode) {
      case 'en':
        return AppLocalizations_en.navTools;
      case 'fr':
      default:
        return AppLocalizations_fr.navTools;
    }
  }
  
  String get navLibrary {
    switch (locale.languageCode) {
      case 'en':
        return AppLocalizations_en.navLibrary;
      case 'fr':
      default:
        return AppLocalizations_fr.navLibrary;
    }
  }
  
  String get navProfile {
    switch (locale.languageCode) {
      case 'en':
        return AppLocalizations_en.navProfile;
      case 'fr':
      default:
        return AppLocalizations_fr.navProfile;
    }
  }
  
  // Search
  String get legalSearch => locale.languageCode == 'en' ? AppLocalizations_en.legalSearch : AppLocalizations_fr.legalSearch;
  // String get filters => locale.languageCode == 'en' ? AppLocalizations_en.filters : AppLocalizations_fr.filters; // DUPLICATE - removed
  String get history => locale.languageCode == 'en' ? AppLocalizations_en.history : AppLocalizations_fr.history;
  String get searchLegalDocuments => locale.languageCode == 'en' ? AppLocalizations_en.searchLegalDocuments : AppLocalizations_fr.searchLegalDocuments;
  String get semanticSearchAI => locale.languageCode == 'en' ? AppLocalizations_en.semanticSearchAI : AppLocalizations_fr.semanticSearchAI;
  String get startYourSearch => locale.languageCode == 'en' ? AppLocalizations_en.startYourSearch : AppLocalizations_fr.startYourSearch;
  String get noResultsFound => locale.languageCode == 'en' ? AppLocalizations_en.noResultsFound : AppLocalizations_fr.noResultsFound;
  String get searchError => locale.languageCode == 'en' ? AppLocalizations_en.searchError : AppLocalizations_fr.searchError;
  
  // Tools Hub
  String get boostYourLearning => locale.languageCode == 'en' ? AppLocalizations_en.boostYourLearning : AppLocalizations_fr.boostYourLearning;
  String get aiToolsForEffectiveRevision => locale.languageCode == 'en' ? AppLocalizations_en.aiToolsForEffectiveRevision : AppLocalizations_fr.aiToolsForEffectiveRevision;
  String get allTools => locale.languageCode == 'en' ? AppLocalizations_en.allTools : AppLocalizations_fr.allTools;
  String get autoGenerateCaseSummaries => locale.languageCode == 'en' ? AppLocalizations_en.autoGenerateCaseSummaries : AppLocalizations_fr.autoGenerateCaseSummaries;
  String get createCustomQuizzes => locale.languageCode == 'en' ? AppLocalizations_en.createCustomQuizzes : AppLocalizations_fr.createCustomQuizzes;
  String get reviewWithSmartFlashcards => locale.languageCode == 'en' ? AppLocalizations_en.reviewWithSmartFlashcards : AppLocalizations_fr.reviewWithSmartFlashcards;
  String get convertAudioToText => locale.languageCode == 'en' ? AppLocalizations_en.convertAudioToText : AppLocalizations_fr.convertAudioToText;
  String get thisMonthUsage => locale.languageCode == 'en' ? AppLocalizations_en.thisMonthUsage : AppLocalizations_fr.thisMonthUsage;
  String get summariesCreated => locale.languageCode == 'en' ? AppLocalizations_en.summariesCreated : AppLocalizations_fr.summariesCreated;
  String get quizzesCreated => locale.languageCode == 'en' ? AppLocalizations_en.quizzesCreated : AppLocalizations_fr.quizzesCreated;
  String get revisionSessions => locale.languageCode == 'en' ? AppLocalizations_en.revisionSessions : AppLocalizations_fr.revisionSessions;
  String get unlockAllTools => locale.languageCode == 'en' ? AppLocalizations_en.unlockAllTools : AppLocalizations_fr.unlockAllTools;
  String get upgradeToStudentPlan => locale.languageCode == 'en' ? AppLocalizations_en.upgradeToStudentPlan : AppLocalizations_fr.upgradeToStudentPlan;
  String get viewPlans => locale.languageCode == 'en' ? AppLocalizations_en.viewPlans : AppLocalizations_fr.viewPlans;
  String get proBadge => locale.languageCode == 'en' ? AppLocalizations_en.proBadge : AppLocalizations_fr.proBadge;
  
  // Fiche Arrêt
  String get automaticCaseSummary => locale.languageCode == 'en' ? AppLocalizations_en.automaticCaseSummary : AppLocalizations_fr.automaticCaseSummary;
  String get pasteDecisionAIGenerates => locale.languageCode == 'en' ? AppLocalizations_en.pasteDecisionAIGenerates : AppLocalizations_fr.pasteDecisionAIGenerates;
  String get jurisdiction => locale.languageCode == 'en' ? AppLocalizations_en.jurisdiction : AppLocalizations_fr.jurisdiction;
  String get selectJurisdiction => locale.languageCode == 'en' ? AppLocalizations_en.selectJurisdiction : AppLocalizations_fr.selectJurisdiction;
  String get pleaseSelectJurisdiction => locale.languageCode == 'en' ? AppLocalizations_en.pleaseSelectJurisdiction : AppLocalizations_fr.pleaseSelectJurisdiction;
  String get legalDomain => locale.languageCode == 'en' ? AppLocalizations_en.legalDomain : AppLocalizations_fr.legalDomain;
  String get selectDomain => locale.languageCode == 'en' ? AppLocalizations_en.selectDomain : AppLocalizations_fr.selectDomain;
  String get pleaseSelectDomain => locale.languageCode == 'en' ? AppLocalizations_en.pleaseSelectDomain : AppLocalizations_fr.pleaseSelectDomain;
  String get decisionText => locale.languageCode == 'en' ? AppLocalizations_en.decisionText : AppLocalizations_fr.decisionText;
  String get pasteCompleteTextHere => locale.languageCode == 'en' ? AppLocalizations_en.pasteCompleteTextHere : AppLocalizations_fr.pasteCompleteTextHere;
  String get pleaseEnterDecisionText => locale.languageCode == 'en' ? AppLocalizations_en.pleaseEnterDecisionText : AppLocalizations_fr.pleaseEnterDecisionText;
  String get textMustContainMin100Chars => locale.languageCode == 'en' ? AppLocalizations_en.textMustContainMin100Chars : AppLocalizations_fr.textMustContainMin100Chars;
  String get generating => locale.languageCode == 'en' ? AppLocalizations_en.generating : AppLocalizations_fr.generating;
  String get generateSummary => locale.languageCode == 'en' ? AppLocalizations_en.generateSummary : AppLocalizations_fr.generateSummary;
  String get generatedSummary => locale.languageCode == 'en' ? AppLocalizations_en.generatedSummary : AppLocalizations_fr.generatedSummary;
  String get copyAll => locale.languageCode == 'en' ? AppLocalizations_en.copyAll : AppLocalizations_fr.copyAll;
  String get jurisdictionReference => locale.languageCode == 'en' ? AppLocalizations_en.jurisdictionReference : AppLocalizations_fr.jurisdictionReference;
  String get parties => locale.languageCode == 'en' ? AppLocalizations_en.parties : AppLocalizations_fr.parties;
  String get facts => locale.languageCode == 'en' ? AppLocalizations_en.facts : AppLocalizations_fr.facts;
  String get procedure => locale.languageCode == 'en' ? AppLocalizations_en.procedure : AppLocalizations_fr.procedure;
  String get claims => locale.languageCode == 'en' ? AppLocalizations_en.claims : AppLocalizations_fr.claims;
  String get legalGrounds => locale.languageCode == 'en' ? AppLocalizations_en.legalGrounds : AppLocalizations_fr.legalGrounds;
  String get decision => locale.languageCode == 'en' ? AppLocalizations_en.decision : AppLocalizations_fr.decision;
  String get scope => locale.languageCode == 'en' ? AppLocalizations_en.scope : AppLocalizations_fr.scope;
  String get pdf => locale.languageCode == 'en' ? AppLocalizations_en.pdf : AppLocalizations_fr.pdf;
  String get docx => locale.languageCode == 'en' ? AppLocalizations_en.docx : AppLocalizations_fr.docx;
  String get exportingPDF => locale.languageCode == 'en' ? AppLocalizations_en.exportingPDF : AppLocalizations_fr.exportingPDF;
  String get exportingDOCX => locale.languageCode == 'en' ? AppLocalizations_en.exportingDOCX : AppLocalizations_fr.exportingDOCX;
  
  // QCM
  String get customQuiz => locale.languageCode == 'en' ? AppLocalizations_en.customQuiz : AppLocalizations_fr.customQuiz;
  String get generateTailoredQuiz => locale.languageCode == 'en' ? AppLocalizations_en.generateTailoredQuiz : AppLocalizations_fr.generateTailoredQuiz;
  String get domain => locale.languageCode == 'en' ? AppLocalizations_en.domain : AppLocalizations_fr.domain;
  String get chooseDomain => locale.languageCode == 'en' ? AppLocalizations_en.chooseDomain : AppLocalizations_fr.chooseDomain;
  String get numberOfQuestions => locale.languageCode == 'en' ? AppLocalizations_en.numberOfQuestions : AppLocalizations_fr.numberOfQuestions;
  String get difficulty => locale.languageCode == 'en' ? AppLocalizations_en.difficulty : AppLocalizations_fr.difficulty;
  String get easy => locale.languageCode == 'en' ? AppLocalizations_en.easy : AppLocalizations_fr.easy;
  String get medium => locale.languageCode == 'en' ? AppLocalizations_en.medium : AppLocalizations_fr.medium;
  String get hard => locale.languageCode == 'en' ? AppLocalizations_en.hard : AppLocalizations_fr.hard;
  String get courseContentOptional => locale.languageCode == 'en' ? AppLocalizations_en.courseContentOptional : AppLocalizations_fr.courseContentOptional;
  String get pasteCourseContent => locale.languageCode == 'en' ? AppLocalizations_en.pasteCourseContent : AppLocalizations_fr.pasteCourseContent;
  String get generateQuiz => locale.languageCode == 'en' ? AppLocalizations_en.generateQuiz : AppLocalizations_fr.generateQuiz;
  String get question => locale.languageCode == 'en' ? AppLocalizations_en.question : AppLocalizations_fr.question;
  String get detailedAnswers => locale.languageCode == 'en' ? AppLocalizations_en.detailedAnswers : AppLocalizations_fr.detailedAnswers;
  String get yourAnswer => locale.languageCode == 'en' ? AppLocalizations_en.yourAnswer : AppLocalizations_fr.yourAnswer;
  String get correctAnswer => locale.languageCode == 'en' ? AppLocalizations_en.correctAnswer : AppLocalizations_fr.correctAnswer;
  String get newQuiz => locale.languageCode == 'en' ? AppLocalizations_en.newQuiz : AppLocalizations_fr.newQuiz;
  
  // Revision Active
  String get activeRevision => locale.languageCode == 'en' ? AppLocalizations_en.activeRevision : AppLocalizations_fr.activeRevision;
  String get smartFlashcardsSpacedRepetition => locale.languageCode == 'en' ? AppLocalizations_en.smartFlashcardsSpacedRepetition : AppLocalizations_fr.smartFlashcardsSpacedRepetition;
  String get cards => locale.languageCode == 'en' ? AppLocalizations_en.cards : AppLocalizations_fr.cards;
  String get mastery => locale.languageCode == 'en' ? AppLocalizations_en.mastery : AppLocalizations_fr.mastery;
  String get streak => locale.languageCode == 'en' ? AppLocalizations_en.streak : AppLocalizations_fr.streak;
  String get howItWorks => locale.languageCode == 'en' ? AppLocalizations_en.howItWorks : AppLocalizations_fr.howItWorks;
  String get readQuestion => locale.languageCode == 'en' ? AppLocalizations_en.readQuestion : AppLocalizations_fr.readQuestion;
  String get takeTimeToUnderstand => locale.languageCode == 'en' ? AppLocalizations_en.takeTimeToUnderstand : AppLocalizations_fr.takeTimeToUnderstand;
  String get thinkAboutAnswer => locale.languageCode == 'en' ? AppLocalizations_en.thinkAboutAnswer : AppLocalizations_fr.thinkAboutAnswer;
  String get tryToFormulateAnswer => locale.languageCode == 'en' ? AppLocalizations_en.tryToFormulateAnswer : AppLocalizations_fr.tryToFormulateAnswer;
  String get flipCard => locale.languageCode == 'en' ? AppLocalizations_en.flipCard : AppLocalizations_fr.flipCard;
  String get checkYourAnswer => locale.languageCode == 'en' ? AppLocalizations_en.checkYourAnswer : AppLocalizations_fr.checkYourAnswer;
  String get rateMastery => locale.languageCode == 'en' ? AppLocalizations_en.rateMastery : AppLocalizations_fr.rateMastery;
  String get indicateHowYouDid => locale.languageCode == 'en' ? AppLocalizations_en.indicateHowYouDid : AppLocalizations_fr.indicateHowYouDid;
  String get startRevision => locale.languageCode == 'en' ? AppLocalizations_en.startRevision : AppLocalizations_fr.startRevision;
  String get endSession => locale.languageCode == 'en' ? AppLocalizations_en.endSession : AppLocalizations_fr.endSession;
  String get confirmEndSession => locale.languageCode == 'en' ? AppLocalizations_en.confirmEndSession : AppLocalizations_fr.confirmEndSession;
  String get tapToSeeAnswer => locale.languageCode == 'en' ? AppLocalizations_en.tapToSeeAnswer : AppLocalizations_fr.tapToSeeAnswer;
  String get tapToSeeQuestion => locale.languageCode == 'en' ? AppLocalizations_en.tapToSeeQuestion : AppLocalizations_fr.tapToSeeQuestion;
  String get howDidYouDo => locale.languageCode == 'en' ? AppLocalizations_en.howDidYouDo : AppLocalizations_fr.howDidYouDo;
  String get didNotKnow => locale.languageCode == 'en' ? AppLocalizations_en.didNotKnow : AppLocalizations_fr.didNotKnow;
  String get hesitated => locale.languageCode == 'en' ? AppLocalizations_en.hesitated : AppLocalizations_fr.hesitated;
  String get knewIt => locale.languageCode == 'en' ? AppLocalizations_en.knewIt : AppLocalizations_fr.knewIt;
  
  // Audio Transcription
  String get audioToTextTranscription => locale.languageCode == 'en' ? AppLocalizations_en.audioToTextTranscription : AppLocalizations_fr.audioToTextTranscription;
  String get convertAudioToActionableText => locale.languageCode == 'en' ? AppLocalizations_en.convertAudioToActionableText : AppLocalizations_fr.convertAudioToActionableText;
  String get recordOrImport => locale.languageCode == 'en' ? AppLocalizations_en.recordOrImport : AppLocalizations_fr.recordOrImport;
  String get recording => locale.languageCode == 'en' ? AppLocalizations_en.recording : AppLocalizations_fr.recording;
  String get stop => locale.languageCode == 'en' ? AppLocalizations_en.stop : AppLocalizations_fr.stop;
  String get recordAudio => locale.languageCode == 'en' ? AppLocalizations_en.recordAudio : AppLocalizations_fr.recordAudio;
  String get recordDirectlyFromDevice => locale.languageCode == 'en' ? AppLocalizations_en.recordDirectlyFromDevice : AppLocalizations_fr.recordDirectlyFromDevice;
  String get record => locale.languageCode == 'en' ? AppLocalizations_en.record : AppLocalizations_fr.record;
  String get import => locale.languageCode == 'en' ? AppLocalizations_en.import : AppLocalizations_fr.import;
  String get fileSelected => locale.languageCode == 'en' ? AppLocalizations_en.fileSelected : AppLocalizations_fr.fileSelected;
  String get transcribing => locale.languageCode == 'en' ? AppLocalizations_en.transcribing : AppLocalizations_fr.transcribing;
  String get transcribe => locale.languageCode == 'en' ? AppLocalizations_en.transcribe : AppLocalizations_fr.transcribe;
  String get transcription => locale.languageCode == 'en' ? AppLocalizations_en.transcription : AppLocalizations_fr.transcription;
  String get copy => locale.languageCode == 'en' ? AppLocalizations_en.copy : AppLocalizations_fr.copy;
  String get exporting => locale.languageCode == 'en' ? AppLocalizations_en.exporting : AppLocalizations_fr.exporting;
  String get transcriptionHistory => locale.languageCode == 'en' ? AppLocalizations_en.transcriptionHistory : AppLocalizations_fr.transcriptionHistory;
  String get opening => locale.languageCode == 'en' ? AppLocalizations_en.opening : AppLocalizations_fr.opening;
  
  // Subscription & Payment
  // String get subscriptionPlans => locale.languageCode == 'en' ? AppLocalizations_en.subscriptionPlans : AppLocalizations_fr.subscriptionPlans; // DUPLICATE - original at line 170
  String get chooseYourPlan => locale.languageCode == 'en' ? AppLocalizations_en.chooseYourPlan : AppLocalizations_fr.chooseYourPlan;
  String get unlockAllFeatures => locale.languageCode == 'en' ? AppLocalizations_en.unlockAllFeatures : AppLocalizations_fr.unlockAllFeatures;
  String get monthly => locale.languageCode == 'en' ? AppLocalizations_en.monthly : AppLocalizations_fr.monthly;
  String get annual => locale.languageCode == 'en' ? AppLocalizations_en.annual : AppLocalizations_fr.annual;
  String get noPlansAvailable => locale.languageCode == 'en' ? AppLocalizations_en.noPlansAvailable : AppLocalizations_fr.noPlansAvailable;
  String get perMonth => locale.languageCode == 'en' ? AppLocalizations_en.perMonth : AppLocalizations_fr.perMonth;
  String get perYear => locale.languageCode == 'en' ? AppLocalizations_en.perYear : AppLocalizations_fr.perYear;
  String get allFeatures => locale.languageCode == 'en' ? AppLocalizations_en.allFeatures : AppLocalizations_fr.allFeatures;
  String get featureChatWithRAG => locale.languageCode == 'en' ? AppLocalizations_en.featureChatWithRAG : AppLocalizations_fr.featureChatWithRAG;
  String get featureLegalLibrary => locale.languageCode == 'en' ? AppLocalizations_en.featureLegalLibrary : AppLocalizations_fr.featureLegalLibrary;
  String get featureSupport14Countries => locale.languageCode == 'en' ? AppLocalizations_en.featureSupport14Countries : AppLocalizations_fr.featureSupport14Countries;
  String get featureMultilanguage => locale.languageCode == 'en' ? AppLocalizations_en.featureMultilanguage : AppLocalizations_fr.featureMultilanguage;
  String get featureDarkMode => locale.languageCode == 'en' ? AppLocalizations_en.featureDarkMode : AppLocalizations_fr.featureDarkMode;
  String get confirmSubscription => locale.languageCode == 'en' ? AppLocalizations_en.confirmSubscription : AppLocalizations_fr.confirmSubscription;
  String get plan => locale.languageCode == 'en' ? AppLocalizations_en.plan : AppLocalizations_fr.plan;
  String get amount => locale.languageCode == 'en' ? AppLocalizations_en.amount : AppLocalizations_fr.amount;
  String get duration => locale.languageCode == 'en' ? AppLocalizations_en.duration : AppLocalizations_fr.duration;
  String get confirm => locale.languageCode == 'en' ? AppLocalizations_en.confirm : AppLocalizations_fr.confirm;
  String get payment => locale.languageCode == 'en' ? AppLocalizations_en.payment : AppLocalizations_fr.payment;
  String get promoCode => locale.languageCode == 'en' ? AppLocalizations_en.promoCode : AppLocalizations_fr.promoCode;
  String get enterYourCode => locale.languageCode == 'en' ? AppLocalizations_en.enterYourCode : AppLocalizations_fr.enterYourCode;
  String get applied => locale.languageCode == 'en' ? AppLocalizations_en.applied : AppLocalizations_fr.applied;
  String get promoCodeAppliedSuccess => locale.languageCode == 'en' ? AppLocalizations_en.promoCodeAppliedSuccess : AppLocalizations_fr.promoCodeAppliedSuccess;
  String get promoCodeInvalid => locale.languageCode == 'en' ? AppLocalizations_en.promoCodeInvalid : AppLocalizations_fr.promoCodeInvalid;
  String get loginToMakePayment => locale.languageCode == 'en' ? AppLocalizations_en.loginToMakePayment : AppLocalizations_fr.loginToMakePayment;
  String get quotaExhausted => locale.languageCode == 'en' ? AppLocalizations_en.quotaExhausted : AppLocalizations_fr.quotaExhausted;
  String get redirectingToPayment => locale.languageCode == 'en' ? AppLocalizations_en.redirectingToPayment : AppLocalizations_fr.redirectingToPayment;
  String get paymentRedirectMessage => locale.languageCode == 'en' ? AppLocalizations_en.paymentRedirectMessage : AppLocalizations_fr.paymentRedirectMessage;
  String get continue_ => locale.languageCode == 'en' ? AppLocalizations_en.continue_ : AppLocalizations_fr.continue_;
  String get paymentVerifying => locale.languageCode == 'en' ? AppLocalizations_en.paymentVerifying : AppLocalizations_fr.paymentVerifying;
  String get paymentVerifiedSuccess => locale.languageCode == 'en' ? AppLocalizations_en.paymentVerifiedSuccess : AppLocalizations_fr.paymentVerifiedSuccess;
  String get paymentConfirmation => locale.languageCode == 'en' ? AppLocalizations_en.paymentConfirmation : AppLocalizations_fr.paymentConfirmation;
  String get confirmPaymentOnPhone => locale.languageCode == 'en' ? AppLocalizations_en.confirmPaymentOnPhone : AppLocalizations_fr.confirmPaymentOnPhone;
  String get transaction => locale.languageCode == 'en' ? AppLocalizations_en.transaction : AppLocalizations_fr.transaction;
  String get paymentSuccess => locale.languageCode == 'en' ? AppLocalizations_en.paymentSuccess : AppLocalizations_fr.paymentSuccess;
  String get subscriptionActivatedSuccess => locale.languageCode == 'en' ? AppLocalizations_en.subscriptionActivatedSuccess : AppLocalizations_fr.subscriptionActivatedSuccess;
  String get paymentSuccessActivationFailed => locale.languageCode == 'en' ? AppLocalizations_en.paymentSuccessActivationFailed : AppLocalizations_fr.paymentSuccessActivationFailed;
  String get securedByFlutterwave => locale.languageCode == 'en' ? AppLocalizations_en.securedByFlutterwave : AppLocalizations_fr.securedByFlutterwave;
  
  // Professional Screens
  String get automaticDetectionAnonymization => locale.languageCode == 'en' ? AppLocalizations_en.automaticDetectionAnonymization : AppLocalizations_fr.automaticDetectionAnonymization;
  String get uploadDocument => locale.languageCode == 'en' ? AppLocalizations_en.uploadDocument : AppLocalizations_fr.uploadDocument;
  String get clickToSelectFile => locale.languageCode == 'en' ? AppLocalizations_en.clickToSelectFile : AppLocalizations_fr.clickToSelectFile;
  String get fileTypesMaxSize => locale.languageCode == 'en' ? AppLocalizations_en.fileTypesMaxSize : AppLocalizations_fr.fileTypesMaxSize;
  String get analyzing => locale.languageCode == 'en' ? AppLocalizations_en.analyzing : AppLocalizations_fr.analyzing;
  String get detectSensitiveData => locale.languageCode == 'en' ? AppLocalizations_en.detectSensitiveData : AppLocalizations_fr.detectSensitiveData;
  String get detectedData => locale.languageCode == 'en' ? AppLocalizations_en.detectedData : AppLocalizations_fr.detectedData;
  String get occurrences => locale.languageCode == 'en' ? AppLocalizations_en.occurrences : AppLocalizations_fr.occurrences;
  String get idCardNumber => locale.languageCode == 'en' ? AppLocalizations_en.idCardNumber : AppLocalizations_fr.idCardNumber;
  String get bankAccountNumber => locale.languageCode == 'en' ? AppLocalizations_en.bankAccountNumber : AppLocalizations_fr.bankAccountNumber;
  String get previewAnonymizedDocument => locale.languageCode == 'en' ? AppLocalizations_en.previewAnonymizedDocument : AppLocalizations_fr.previewAnonymizedDocument;
  String get seeResultBeforeDownload => locale.languageCode == 'en' ? AppLocalizations_en.seeResultBeforeDownload : AppLocalizations_fr.seeResultBeforeDownload;
  String get anonymizedDocumentPreview => locale.languageCode == 'en' ? AppLocalizations_en.anonymizedDocumentPreview : AppLocalizations_fr.anonymizedDocumentPreview;
  String get downloadAnonymizedDocument => locale.languageCode == 'en' ? AppLocalizations_en.downloadAnonymizedDocument : AppLocalizations_fr.downloadAnonymizedDocument;
  String get anonymizationHistory => locale.languageCode == 'en' ? AppLocalizations_en.anonymizationHistory : AppLocalizations_fr.anonymizationHistory;
  String get itemsHidden => locale.languageCode == 'en' ? AppLocalizations_en.itemsHidden : AppLocalizations_fr.itemsHidden;
  String get downloading => locale.languageCode == 'en' ? AppLocalizations_en.downloading : AppLocalizations_fr.downloading;
  String get news => locale.languageCode == 'en' ? AppLocalizations_en.news : AppLocalizations_fr.news;
  String get myAlerts => locale.languageCode == 'en' ? AppLocalizations_en.myAlerts : AppLocalizations_fr.myAlerts;
  String get legalNews => locale.languageCode == 'en' ? AppLocalizations_en.legalNews : AppLocalizations_fr.legalNews;
  String get stayInformed => locale.languageCode == 'en' ? AppLocalizations_en.stayInformed : AppLocalizations_fr.stayInformed;
  String get quickFilters => locale.languageCode == 'en' ? AppLocalizations_en.quickFilters : AppLocalizations_fr.quickFilters;
  String get new_ => locale.languageCode == 'en' ? AppLocalizations_en.new_ : AppLocalizations_fr.new_;
  String get thisWeek => locale.languageCode == 'en' ? AppLocalizations_en.thisWeek : AppLocalizations_fr.thisWeek;
  String get myTopics => locale.languageCode == 'en' ? AppLocalizations_en.myTopics : AppLocalizations_fr.myTopics;
  String get newBadge => locale.languageCode == 'en' ? AppLocalizations_en.newBadge : AppLocalizations_fr.newBadge;
  String get read => locale.languageCode == 'en' ? AppLocalizations_en.read : AppLocalizations_fr.read;
  String get configureAlerts => locale.languageCode == 'en' ? AppLocalizations_en.configureAlerts : AppLocalizations_fr.configureAlerts;
  String get legalDomains => locale.languageCode == 'en' ? AppLocalizations_en.legalDomains : AppLocalizations_fr.legalDomains;
  String get jurisdictions => locale.languageCode == 'en' ? AppLocalizations_en.jurisdictions : AppLocalizations_fr.jurisdictions;
  String get notificationSettings => locale.languageCode == 'en' ? AppLocalizations_en.notificationSettings : AppLocalizations_fr.notificationSettings;
  String get pushNotifications => locale.languageCode == 'en' ? AppLocalizations_en.pushNotifications : AppLocalizations_fr.pushNotifications;
  String get receiveRealTimeAlerts => locale.languageCode == 'en' ? AppLocalizations_en.receiveRealTimeAlerts : AppLocalizations_fr.receiveRealTimeAlerts;
  String get dailyEmail => locale.languageCode == 'en' ? AppLocalizations_en.dailyEmail : AppLocalizations_fr.dailyEmail;
  String get dailyNewsSummary => locale.languageCode == 'en' ? AppLocalizations_en.dailyNewsSummary : AppLocalizations_fr.dailyNewsSummary;
  String get urgentAlertsOnly => locale.languageCode == 'en' ? AppLocalizations_en.urgentAlertsOnly : AppLocalizations_fr.urgentAlertsOnly;
  String get onlyMajorChanges => locale.languageCode == 'en' ? AppLocalizations_en.onlyMajorChanges : AppLocalizations_fr.onlyMajorChanges;
  String get alertsConfiguredSuccess => locale.languageCode == 'en' ? AppLocalizations_en.alertsConfiguredSuccess : AppLocalizations_fr.alertsConfiguredSuccess;
  String get saveAlerts => locale.languageCode == 'en' ? AppLocalizations_en.saveAlerts : AppLocalizations_fr.saveAlerts;
  
  // Chat & Documents
  String get aiChat => locale.languageCode == 'en' ? AppLocalizations_en.aiChat : AppLocalizations_fr.aiChat;
  String get chatHistory => locale.languageCode == 'en' ? AppLocalizations_en.chatHistory : AppLocalizations_fr.chatHistory;
  String get deleteConversation => locale.languageCode == 'en' ? AppLocalizations_en.deleteConversation : AppLocalizations_fr.deleteConversation;
  String get suggestions => locale.languageCode == 'en' ? AppLocalizations_en.suggestions : AppLocalizations_fr.suggestions;
  String get startConversation => locale.languageCode == 'en' ? AppLocalizations_en.startConversation : AppLocalizations_fr.startConversation;
  String get dossyIsTyping => locale.languageCode == 'en' ? AppLocalizations_en.dossyIsTyping : AppLocalizations_fr.dossyIsTyping;
  String get alwaysVerifyLegalInfo => locale.languageCode == 'en' ? AppLocalizations_en.alwaysVerifyLegalInfo : AppLocalizations_fr.alwaysVerifyLegalInfo;
  String get askLegalQuestion => locale.languageCode == 'en' ? AppLocalizations_en.askLegalQuestion : AppLocalizations_fr.askLegalQuestion;
  String get pleaseLogin => locale.languageCode == 'en' ? AppLocalizations_en.pleaseLogin : AppLocalizations_fr.pleaseLogin;
  String get analysisQuotaExhausted => locale.languageCode == 'en' ? AppLocalizations_en.analysisQuotaExhausted : AppLocalizations_fr.analysisQuotaExhausted;
  String get myDocuments => locale.languageCode == 'en' ? AppLocalizations_en.myDocuments : AppLocalizations_fr.myDocuments;
  String get uploads => locale.languageCode == 'en' ? AppLocalizations_en.uploads : AppLocalizations_fr.uploads;
  String get noDocuments => locale.languageCode == 'en' ? AppLocalizations_en.noDocuments : AppLocalizations_fr.noDocuments;
  String get uploadDocumentsForAdvancedRAG => locale.languageCode == 'en' ? AppLocalizations_en.uploadDocumentsForAdvancedRAG : AppLocalizations_fr.uploadDocumentsForAdvancedRAG;
  String get uploadDocumentsAndAskChat => locale.languageCode == 'en' ? AppLocalizations_en.uploadDocumentsAndAskChat : AppLocalizations_fr.uploadDocumentsAndAskChat;
  String get uploading => locale.languageCode == 'en' ? AppLocalizations_en.uploading : AppLocalizations_fr.uploading;
  String get deleteDocument => locale.languageCode == 'en' ? AppLocalizations_en.deleteDocument : AppLocalizations_fr.deleteDocument;
  String get confirmDeleteDocument => locale.languageCode == 'en' ? AppLocalizations_en.confirmDeleteDocument : AppLocalizations_fr.confirmDeleteDocument;
  String get documentDeleted => locale.languageCode == 'en' ? AppLocalizations_en.documentDeleted : AppLocalizations_fr.documentDeleted;
  String get deletionError => locale.languageCode == 'en' ? AppLocalizations_en.deletionError : AppLocalizations_fr.deletionError;
  String get uploadQuotaExhausted => locale.languageCode == 'en' ? AppLocalizations_en.uploadQuotaExhausted : AppLocalizations_fr.uploadQuotaExhausted;
  String get documentUploadedSuccess => locale.languageCode == 'en' ? AppLocalizations_en.documentUploadedSuccess : AppLocalizations_fr.documentUploadedSuccess;
  String get uploadError => locale.languageCode == 'en' ? AppLocalizations_en.uploadError : AppLocalizations_fr.uploadError;
  
  // Library Hub
  String get proLibrary => locale.languageCode == 'en' ? AppLocalizations_en.proLibrary : AppLocalizations_fr.proLibrary;
  String get professionalResources => locale.languageCode == 'en' ? AppLocalizations_en.professionalResources : AppLocalizations_fr.professionalResources;
  String get accessProfessionalTools => locale.languageCode == 'en' ? AppLocalizations_en.accessProfessionalTools : AppLocalizations_fr.accessProfessionalTools;
  // 'documentTemplates' already declared earlier; removed duplicate here
  String get downloadTemplatesForYourPlan => locale.languageCode == 'en' ? AppLocalizations_en.downloadTemplatesForYourPlan : AppLocalizations_fr.downloadTemplatesForYourPlan;
  // 'fiscalResources' already declared earlier; removed duplicate here
  String get accessTaxCodesFinanceLaws => locale.languageCode == 'en' ? AppLocalizations_en.accessTaxCodesFinanceLaws : AppLocalizations_fr.accessTaxCodesFinanceLaws;
  String get legalLibrary => locale.languageCode == 'en' ? AppLocalizations_en.legalLibrary : AppLocalizations_fr.legalLibrary;
  String get searchLegalKnowledgeBase => locale.languageCode == 'en' ? AppLocalizations_en.searchLegalKnowledgeBase : AppLocalizations_fr.searchLegalKnowledgeBase;
  String get upgradeToProToUnlock => locale.languageCode == 'en' ? AppLocalizations_en.upgradeToProToUnlock : AppLocalizations_fr.upgradeToProToUnlock;
  
  // Widgets
  String get recommended => locale.languageCode == 'en' ? AppLocalizations_en.recommended : AppLocalizations_fr.recommended;
  String get current => locale.languageCode == 'en' ? AppLocalizations_en.current : AppLocalizations_fr.current;
  String get free => locale.languageCode == 'en' ? AppLocalizations_en.free : AppLocalizations_fr.free;
  // 'currentPlan' already declared earlier; removed duplicate here
  String get startForFree => locale.languageCode == 'en' ? AppLocalizations_en.startForFree : AppLocalizations_fr.startForFree;
  String get chooseThisPlan => locale.languageCode == 'en' ? AppLocalizations_en.chooseThisPlan : AppLocalizations_fr.chooseThisPlan;
  // 'noData' already declared earlier; removed duplicate here
  String get noInformationAvailable => locale.languageCode == 'en' ? AppLocalizations_en.noInformationAvailable : AppLocalizations_fr.noInformationAvailable;
  String get noConnection => locale.languageCode == 'en' ? AppLocalizations_en.noConnection : AppLocalizations_fr.noConnection;
  String get checkInternetConnection => locale.languageCode == 'en' ? AppLocalizations_en.checkInternetConnection : AppLocalizations_fr.checkInternetConnection;
  String get errorOccurredRetry => locale.languageCode == 'en' ? AppLocalizations_en.errorOccurredRetry : AppLocalizations_fr.errorOccurredRetry;
  String get noSearchResults => locale.languageCode == 'en' ? AppLocalizations_en.noSearchResults : AppLocalizations_fr.noSearchResults;
  String get noResultsFoundFor => locale.languageCode == 'en' ? AppLocalizations_en.noResultsFoundFor : AppLocalizations_fr.noResultsFoundFor;
  String get noMatchingResults => locale.languageCode == 'en' ? AppLocalizations_en.noMatchingResults : AppLocalizations_fr.noMatchingResults;
  String get clearSearch => locale.languageCode == 'en' ? AppLocalizations_en.clearSearch : AppLocalizations_fr.clearSearch;
  String get noMessages => locale.languageCode == 'en' ? AppLocalizations_en.noMessages : AppLocalizations_fr.noMessages;
  String get startConversationWithAI => locale.languageCode == 'en' ? AppLocalizations_en.startConversationWithAI : AppLocalizations_fr.startConversationWithAI;
  String get startChat => locale.languageCode == 'en' ? AppLocalizations_en.startChat : AppLocalizations_fr.startChat;
  String get emptyLibrary => locale.languageCode == 'en' ? AppLocalizations_en.emptyLibrary : AppLocalizations_fr.emptyLibrary;
  String get noDocumentsUploaded => locale.languageCode == 'en' ? AppLocalizations_en.noDocumentsUploaded : AppLocalizations_fr.noDocumentsUploaded;
  String get addDocument => locale.languageCode == 'en' ? AppLocalizations_en.addDocument : AppLocalizations_fr.addDocument;
  String get emptyHistory => locale.languageCode == 'en' ? AppLocalizations_en.emptyHistory : AppLocalizations_fr.emptyHistory;
  String get noHistoryOf => locale.languageCode == 'en' ? AppLocalizations_en.noHistoryOf : AppLocalizations_fr.noHistoryOf;
  String get historyEmptyForNow => locale.languageCode == 'en' ? AppLocalizations_en.historyEmptyForNow : AppLocalizations_fr.historyEmptyForNow;
  String get noNotifications => locale.languageCode == 'en' ? AppLocalizations_en.noNotifications : AppLocalizations_fr.noNotifications;
  String get nothingForNow => locale.languageCode == 'en' ? AppLocalizations_en.nothingForNow : AppLocalizations_fr.nothingForNow;
  String get noFavorites => locale.languageCode == 'en' ? AppLocalizations_en.noFavorites : AppLocalizations_fr.noFavorites;
  String get noArticlesAdded => locale.languageCode == 'en' ? AppLocalizations_en.noArticlesAdded : AppLocalizations_fr.noArticlesAdded;
  String get quotaExceeded => locale.languageCode == 'en' ? AppLocalizations_en.quotaExceeded : AppLocalizations_fr.quotaExceeded;
  String get quotaExceededUpgrade => locale.languageCode == 'en' ? AppLocalizations_en.quotaExceededUpgrade : AppLocalizations_fr.quotaExceededUpgrade;
  String get upgrade => locale.languageCode == 'en' ? AppLocalizations_en.upgrade : AppLocalizations_fr.upgrade;
  String get proFeature => locale.languageCode == 'en' ? AppLocalizations_en.proFeature : AppLocalizations_fr.proFeature;
  String get featureReservedForPremium => locale.languageCode == 'en' ? AppLocalizations_en.featureReservedForPremium : AppLocalizations_fr.featureReservedForPremium;
  String get thisFeatureReservedForPremium => locale.languageCode == 'en' ? AppLocalizations_en.thisFeatureReservedForPremium : AppLocalizations_fr.thisFeatureReservedForPremium;
  String get maintenanceInProgress => locale.languageCode == 'en' ? AppLocalizations_en.maintenanceInProgress : AppLocalizations_fr.maintenanceInProgress;
  String get temporarilyUnavailable => locale.languageCode == 'en' ? AppLocalizations_en.temporarilyUnavailable : AppLocalizations_fr.temporarilyUnavailable;
  String get updateRequired => locale.languageCode == 'en' ? AppLocalizations_en.updateRequired : AppLocalizations_fr.updateRequired;
  String get updateRequiredMessage => locale.languageCode == 'en' ? AppLocalizations_en.updateRequiredMessage : AppLocalizations_fr.updateRequiredMessage;
  String get updateAction => locale.languageCode == 'en' ? AppLocalizations_en.updateAction : AppLocalizations_fr.updateAction;
  String get selectCategory => locale.languageCode == 'en' ? AppLocalizations_en.selectCategory : AppLocalizations_fr.selectCategory;
  String get jurisprudence => locale.languageCode == 'en' ? AppLocalizations_en.jurisprudence : AppLocalizations_fr.jurisprudence;
  String get legislation => locale.languageCode == 'en' ? AppLocalizations_en.legislation : AppLocalizations_fr.legislation;
  String get doctrine => locale.languageCode == 'en' ? AppLocalizations_en.doctrine : AppLocalizations_fr.doctrine;
  String get documentInformation => locale.languageCode == 'en' ? AppLocalizations_en.documentInformation : AppLocalizations_fr.documentInformation;
  String get publicationDate => locale.languageCode == 'en' ? AppLocalizations_en.publicationDate : AppLocalizations_fr.publicationDate;
  String get creationDate => locale.languageCode == 'en' ? AppLocalizations_en.creationDate : AppLocalizations_fr.creationDate;
  String get view => locale.languageCode == 'en' ? AppLocalizations_en.view : AppLocalizations_fr.view;
  
  // Additional translations
  String get promoCodeApplied => locale.languageCode == 'en' ? AppLocalizations_en.promoCodeApplied : AppLocalizations_fr.promoCodeApplied;
  String get promoCodeInvalidError => locale.languageCode == 'en' ? AppLocalizations_en.promoCodeInvalidError : AppLocalizations_fr.promoCodeInvalidError;
  String get redirectingPayment => locale.languageCode == 'en' ? AppLocalizations_en.redirectingPayment : AppLocalizations_fr.redirectingPayment;
  String get paymentCompleted => locale.languageCode == 'en' ? AppLocalizations_en.paymentCompleted : AppLocalizations_fr.paymentCompleted;
  String get paymentVerified => locale.languageCode == 'en' ? AppLocalizations_en.paymentVerified : AppLocalizations_fr.paymentVerified;
  String get paymentConfirmed => locale.languageCode == 'en' ? AppLocalizations_en.paymentConfirmed : AppLocalizations_fr.paymentConfirmed;
  String get securedPayment => locale.languageCode == 'en' ? AppLocalizations_en.securedPayment : AppLocalizations_fr.securedPayment;
  String get cannotOpenPaymentLink => locale.languageCode == 'en' ? AppLocalizations_en.cannotOpenPaymentLink : AppLocalizations_fr.cannotOpenPaymentLink;
  String get retryAction => locale.languageCode == 'en' ? AppLocalizations_en.retryAction : AppLocalizations_fr.retryAction;
  String get openInBrowser => locale.languageCode == 'en' ? AppLocalizations_en.openInBrowser : AppLocalizations_fr.openInBrowser;
  String get fiscal => locale.languageCode == 'en' ? AppLocalizations_en.fiscal : AppLocalizations_fr.fiscal;
  String get social => locale.languageCode == 'en' ? AppLocalizations_en.social : AppLocalizations_fr.social;
  String get copiedToClipboard => locale.languageCode == 'en' ? AppLocalizations_en.copiedToClipboard : AppLocalizations_fr.copiedToClipboard;
  String get pdfExportInProgress => locale.languageCode == 'en' ? AppLocalizations_en.pdfExportInProgress : AppLocalizations_fr.pdfExportInProgress;
  String get docxExportInProgress => locale.languageCode == 'en' ? AppLocalizations_en.docxExportInProgress : AppLocalizations_fr.docxExportInProgress;
  String get userNotAuthenticated => locale.languageCode == 'en' ? AppLocalizations_en.userNotAuthenticated : AppLocalizations_fr.userNotAuthenticated;
  String get conversationLoaded => locale.languageCode == 'en' ? AppLocalizations_en.conversationLoaded : AppLocalizations_fr.conversationLoaded;
  String get conversationDeleted => locale.languageCode == 'en' ? AppLocalizations_en.conversationDeleted : AppLocalizations_fr.conversationDeleted;
  String get conversationHistory => locale.languageCode == 'en' ? AppLocalizations_en.conversationHistory : AppLocalizations_fr.conversationHistory;
  String get clearCache => locale.languageCode == 'en' ? AppLocalizations_en.clearCache : AppLocalizations_fr.clearCache;
  String get clearCacheAction => locale.languageCode == 'en' ? AppLocalizations_en.clearCacheAction : AppLocalizations_fr.clearCacheAction;
  String get cacheCleared => locale.languageCode == 'en' ? AppLocalizations_en.cacheCleared : AppLocalizations_fr.cacheCleared;
  String get logoutConfirmMessage => locale.languageCode == 'en' ? AppLocalizations_en.logoutConfirmMessage : AppLocalizations_fr.logoutConfirmMessage;
  String get disconnection => locale.languageCode == 'en' ? AppLocalizations_en.disconnection : AppLocalizations_fr.disconnection;
  String get calculationResult => locale.languageCode == 'en' ? AppLocalizations_en.calculationResult : AppLocalizations_fr.calculationResult;
  String get saveAction => locale.languageCode == 'en' ? AppLocalizations_en.saveAction : AppLocalizations_fr.saveAction;
  String get shareToImplement => locale.languageCode == 'en' ? AppLocalizations_en.shareToImplement : AppLocalizations_fr.shareToImplement;
  String get pdfDownloadToImplement => locale.languageCode == 'en' ? AppLocalizations_en.pdfDownloadToImplement : AppLocalizations_fr.pdfDownloadToImplement;
  String get resultSaved => locale.languageCode == 'en' ? AppLocalizations_en.resultSaved : AppLocalizations_fr.resultSaved;
  String get documentDetails => locale.languageCode == 'en' ? AppLocalizations_en.documentDetails : AppLocalizations_fr.documentDetails;
  String get showAllDocuments => locale.languageCode == 'en' ? AppLocalizations_en.showAllDocuments : AppLocalizations_fr.showAllDocuments;
  String get calculatorsSimulatorsTitle => locale.languageCode == 'en' ? AppLocalizations_en.calculatorsSimulatorsTitle : AppLocalizations_fr.calculatorsSimulatorsTitle;
  String get noCalculatorAvailable => locale.languageCode == 'en' ? AppLocalizations_en.noCalculatorAvailable : AppLocalizations_fr.noCalculatorAvailable;
  String get documentTemplatesTitle => locale.languageCode == 'en' ? AppLocalizations_en.documentTemplatesTitle : AppLocalizations_fr.documentTemplatesTitle;
  String get fiscalResourcesTitle => locale.languageCode == 'en' ? AppLocalizations_en.fiscalResourcesTitle : AppLocalizations_fr.fiscalResourcesTitle;
  String get searchDocuments => locale.languageCode == 'en' ? AppLocalizations_en.searchDocuments : AppLocalizations_fr.searchDocuments;
  String get loadMore => locale.languageCode == 'en' ? AppLocalizations_en.loadMore : AppLocalizations_fr.loadMore;
  String get subAccountCreationFailed => locale.languageCode == 'en' ? AppLocalizations_en.subAccountCreationFailed : AppLocalizations_fr.subAccountCreationFailed;
  String get accountActivated => locale.languageCode == 'en' ? AppLocalizations_en.accountActivated : AppLocalizations_fr.accountActivated;
  String get accountDisabled => locale.languageCode == 'en' ? AppLocalizations_en.accountDisabled : AppLocalizations_fr.accountDisabled;
  String get accountDeleted => locale.languageCode == 'en' ? AppLocalizations_en.accountDeleted : AppLocalizations_fr.accountDeleted;
  String get createFirstSubAccount => locale.languageCode == 'en' ? AppLocalizations_en.createFirstSubAccount : AppLocalizations_fr.createFirstSubAccount;
  String get mustBeAuthenticatedForSubAccount => locale.languageCode == 'en' ? AppLocalizations_en.mustBeAuthenticatedForSubAccount : AppLocalizations_fr.mustBeAuthenticatedForSubAccount;
  String get noDocumentFound => locale.languageCode == 'en' ? AppLocalizations_en.noDocumentFound : AppLocalizations_fr.noDocumentFound;
  String get noTaxParameters => locale.languageCode == 'en' ? AppLocalizations_en.noTaxParameters : AppLocalizations_fr.noTaxParameters;
  String get creatingInProgress => locale.languageCode == 'en' ? AppLocalizations_en.creatingInProgress : AppLocalizations_fr.creatingInProgress;
  String get confirmDeletion => locale.languageCode == 'en' ? AppLocalizations_en.confirmDeletion : AppLocalizations_fr.confirmDeletion;
  String get taxParameters => locale.languageCode == 'en' ? AppLocalizations_en.taxParameters : AppLocalizations_fr.taxParameters;
  String get taxCodeCGI => locale.languageCode == 'en' ? AppLocalizations_en.taxCodeCGI : AppLocalizations_fr.taxCodeCGI;
  String get taxCodeLPF => locale.languageCode == 'en' ? AppLocalizations_en.taxCodeLPF : AppLocalizations_fr.taxCodeLPF;
  String get calculationSuccessful => locale.languageCode == 'en' ? AppLocalizations_en.calculationSuccessful : AppLocalizations_fr.calculationSuccessful;
  String get noResourceFound => locale.languageCode == 'en' ? AppLocalizations_en.noResourceFound : AppLocalizations_fr.noResourceFound;
  String get noTemplateFound => locale.languageCode == 'en' ? AppLocalizations_en.noTemplateFound : AppLocalizations_fr.noTemplateFound;
  String get accountDeactivated => locale.languageCode == 'en' ? AppLocalizations_en.accountDeactivated : AppLocalizations_fr.accountDeactivated;
  String get departmentOptional => locale.languageCode == 'en' ? AppLocalizations_en.departmentOptional : AppLocalizations_fr.departmentOptional;
  String get notConnected => locale.languageCode == 'en' ? AppLocalizations_en.notConnected : AppLocalizations_fr.notConnected;
  String get loadingError => locale.languageCode == 'en' ? AppLocalizations_en.loadingError : AppLocalizations_fr.loadingError;
  String get errorMessage => locale.languageCode == 'en' ? AppLocalizations_en.errorMessage : AppLocalizations_fr.errorMessage;
  String get noConversations => locale.languageCode == 'en' ? AppLocalizations_en.noConversations : AppLocalizations_fr.noConversations;
  String get startNewConversation => locale.languageCode == 'en' ? AppLocalizations_en.startNewConversation : AppLocalizations_fr.startNewConversation;
  String get noPlanAvailable => locale.languageCode == 'en' ? AppLocalizations_en.noPlanAvailable : AppLocalizations_fr.noPlanAvailable;
  String get planLabel => locale.languageCode == 'en' ? AppLocalizations_en.planLabel : AppLocalizations_fr.planLabel;
  String get amountLabel => locale.languageCode == 'en' ? AppLocalizations_en.amountLabel : AppLocalizations_fr.amountLabel;
  String get durationLabel => locale.languageCode == 'en' ? AppLocalizations_en.durationLabel : AppLocalizations_fr.durationLabel;
  String get confirmButton => locale.languageCode == 'en' ? AppLocalizations_en.confirmButton : AppLocalizations_fr.confirmButton;
  
  String get deleteConversationConfirm => locale.languageCode == 'en' ? AppLocalizations_en.deleteConversationConfirm : AppLocalizations_fr.deleteConversationConfirm;
  String get clearCacheConfirm => locale.languageCode == 'en' ? AppLocalizations_en.clearCacheConfirm : AppLocalizations_fr.clearCacheConfirm;
  String get fileDownloaded => locale.languageCode == 'en' ? AppLocalizations_en.fileDownloaded : AppLocalizations_fr.fileDownloaded;
  String get openFile => locale.languageCode == 'en' ? AppLocalizations_en.openFile : AppLocalizations_fr.openFile;
  String get cannotOpenFile => locale.languageCode == 'en' ? AppLocalizations_en.cannotOpenFile : AppLocalizations_fr.cannotOpenFile;
  String get favorite => locale.languageCode == 'en' ? AppLocalizations_en.favorite : AppLocalizations_fr.favorite;
  String get print => locale.languageCode == 'en' ? AppLocalizations_en.print : AppLocalizations_fr.print;
}

class _AppLocalizationsDelegate extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  bool isSupported(Locale locale) {
    return ['fr', 'en'].contains(locale.languageCode);
  }

    @override
    Future<AppLocalizations> load(Locale locale) async {
    return AppLocalizations(locale);
    }

    @override
    bool shouldReload(covariant LocalizationsDelegate<AppLocalizations> old) => false;
  }


