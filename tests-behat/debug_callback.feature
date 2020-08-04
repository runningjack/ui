Feature: Callback
  Testing callbacks

  Scenario:
    Given I am on "layout/debug_panel.php"
    Given I press button "Button 1"
    When I should see "Panel 1"
    When I press button "Reload Myself"
# currently (without global sticky), Modal is opened thus button is not clickable
    Then I press button "Reload Myself"
