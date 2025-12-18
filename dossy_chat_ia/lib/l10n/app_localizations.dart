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

  // Get current language translations
  dynamic get _localizedValues {
    switch (locale.languageCode) {
      case 'en':
        return AppLocalizations_en;
      case 'fr':
      default:
        return AppLocalizations_fr;
    }
  }

  // General
  String get appName => _localizedValues.appName;
  String get welcome => _localizedValues.welcome;
  String get loading => _localizedValues.loading;
  String get error => _localizedValues.error;
  String get retry => _localizedValues.retry;
  String get cancel => _localizedValues.cancel;
  String get save => _localizedValues.save;
  String get delete => _localizedValues.delete;
  String get edit => _localizedValues.edit;
  String get create => _localizedValues.create;
  String get search => _localizedValues.search;
  String get filter => _localizedValues.filter;
  String get apply => _localizedValues.apply;
  String get reset => _localizedValues.reset;
  String get close => _localizedValues.close;
  String get yes => _localizedValues.yes;
  String get no => _localizedValues.no;
  String get ok => _localizedValues.ok;
  String get back => _localizedValues.back;
  String get next => _localizedValues.next;
  String get previous => _localizedValues.previous;
  String get finish => _localizedValues.finish;
  String get share => _localizedValues.share;
  String get download => _localizedValues.download;
  String get upload => _localizedValues.upload;
  String get noData => _localizedValues.noData;
  String get noResults => _localizedValues.noResults;

  // Authentication
  String get login => _localizedValues.login;
  String get register => _localizedValues.register;
  String get logout => _localizedValues.logout;
  String get email => _localizedValues.email;
  String get password => _localizedValues.password;
  String get confirmPassword => _localizedValues.confirmPassword;
  String get forgotPassword => _localizedValues.forgotPassword;
  String get resetPassword => _localizedValues.resetPassword;
  String get name => _localizedValues.name;
  String get phone => _localizedValues.phone;
  String get country => _localizedValues.country;
  String get selectCountry => _localizedValues.selectCountry;

  // Navigation
  String get home => _localizedValues.home;
  String get chat => _localizedValues.chat;
  String get documents => _localizedValues.documents;
  String get templates => _localizedValues.templates;
  String get calculators => _localizedValues.calculators;
  String get fiscalResources => _localizedValues.fiscalResources;
  String get legalAlerts => _localizedValues.legalAlerts;
  String get enterprise => _localizedValues.enterprise;
  String get profile => _localizedValues.profile;
  String get settings => _localizedValues.settings;
  String get subscription => _localizedValues.subscription;

  // Templates
  String get documentTemplates => _localizedValues.documentTemplates;
  String get templatesList => _localizedValues.templatesList;
  String get templateDetails => _localizedValues.templateDetails;
  String get downloadTemplate => _localizedValues.downloadTemplate;
  String get downloading => _localizedValues.downloading;
  String get searchTemplates => _localizedValues.searchTemplates;
  String get noTemplates => _localizedValues.noTemplates;
  String get views => _localizedValues.views;
  String get downloads => _localizedValues.downloads;
  String get usageInstructions => _localizedValues.usageInstructions;
  String get tags => _localizedValues.tags;
  String get supportedCountries => _localizedValues.supportedCountries;

  // Calculators
  String get calculatorsSimulators => _localizedValues.calculatorsSimulators;
  String get calculatorsList => _localizedValues.calculatorsList;
  String get calculate => _localizedValues.calculate;
  String get calculating => _localizedValues.calculating;
  String get result => _localizedValues.result;
  String get results => _localizedValues.results;
  String get calculation => _localizedValues.calculation;
  String get calculationHistory => _localizedValues.calculationHistory;
  String get newCalculation => _localizedValues.newCalculation;
  String get enterInformation => _localizedValues.enterInformation;
  String get calculationDetails => _localizedValues.calculationDetails;
  String get breakdown => _localizedValues.breakdown;
  String get usedData => _localizedValues.usedData;
  String get legalNotice => _localizedValues.legalNotice;
  String get importantNotes => _localizedValues.importantNotes;
  String get saveToHistory => _localizedValues.saveToHistory;
  String get savedSuccessfully => _localizedValues.savedSuccessfully;
  String get calculationSuccess => _localizedValues.calculationSuccess;
  String get calculationError => _localizedValues.calculationError;
  String get requiredField => _localizedValues.requiredField;
  String get invalidNumber => _localizedValues.invalidNumber;

  // Fiscal Resources
  String get fiscalSocialResources => _localizedValues.fiscalSocialResources;
  String get resourcesList => _localizedValues.resourcesList;
  String get resourceDetails => _localizedValues.resourceDetails;
  String get searchResources => _localizedValues.searchResources;
  String get noResources => _localizedValues.noResources;
  String get resourceType => _localizedValues.resourceType;
  String get applicableYear => _localizedValues.applicableYear;
  String get version => _localizedValues.version;
  String get resourceContent => _localizedValues.resourceContent;
  String get legalReferences => _localizedValues.legalReferences;

  // Legal Alerts
  String get legalAlertsTitle => _localizedValues.legalAlertsTitle;
  String get alertsList => _localizedValues.alertsList;
  String get alertDetails => _localizedValues.alertDetails;
  String get unreadAlerts => _localizedValues.unreadAlerts;
  String get markAsRead => _localizedValues.markAsRead;
  String get noAlerts => _localizedValues.noAlerts;

  // Enterprise
  String get enterpriseDashboard => _localizedValues.enterpriseDashboard;
  String get subAccounts => _localizedValues.subAccounts;
  String get createSubAccount => _localizedValues.createSubAccount;
  String get subAccountDetails => _localizedValues.subAccountDetails;
  String get totalSubAccounts => _localizedValues.totalSubAccounts;
  String get activeSubAccounts => _localizedValues.activeSubAccounts;
  String get quotaUsage => _localizedValues.quotaUsage;
  String get recentActivity => _localizedValues.recentActivity;
  String get manageSubAccounts => _localizedValues.manageSubAccounts;
  String get fullName => _localizedValues.fullName;
  String get department => _localizedValues.department;
  String get role => _localizedValues.role;
  String get rolePermissions => _localizedValues.rolePermissions;
  String get administrator => _localizedValues.administrator;
  String get manager => _localizedValues.manager;
  String get accountant => _localizedValues.accountant;
  String get hr => _localizedValues.hr;
  String get viewer => _localizedValues.viewer;
  String get createAccount => _localizedValues.createAccount;
  String get creating => _localizedValues.creating;
  String get subAccountCreated => _localizedValues.subAccountCreated;
  String get invalidEmail => _localizedValues.invalidEmail;

  // Subscription Plans
  String get subscriptionPlans => _localizedValues.subscriptionPlans;
  String get currentPlan => _localizedValues.currentPlan;
  String get upgradePlan => _localizedValues.upgradePlan;
  String get freePlan => _localizedValues.freePlan;
  String get studentPlan => _localizedValues.studentPlan;
  String get professionalPlan => _localizedValues.professionalPlan;
  String get enterprisePlan => _localizedValues.enterprisePlan;
  String get subscribe => _localizedValues.subscribe;
  String get features => _localizedValues.features;
  String get unlimited => _localizedValues.unlimited;

  // Errors
  String get errorOccurred => _localizedValues.errorOccurred;
  String get tryAgain => _localizedValues.tryAgain;
  String get networkError => _localizedValues.networkError;
  String get serverError => _localizedValues.serverError;
  String get notFound => _localizedValues.notFound;
  String get accessDenied => _localizedValues.accessDenied;
  String get upgradeRequired => _localizedValues.upgradeRequired;
  String get featureNotAvailable => _localizedValues.featureNotAvailable;

  // Common
  String get description => _localizedValues.description;
  String get title => _localizedValues.title;
  String get type => _localizedValues.type;
  String get status => _localizedValues.status;
  String get active => _localizedValues.active;
  String get inactive => _localizedValues.inactive;
  String get all => _localizedValues.all;
  String get filters => _localizedValues.filters;
  String get applyFilters => _localizedValues.applyFilters;
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
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}
