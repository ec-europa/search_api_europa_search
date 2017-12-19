@api
Feature: Setup

  Scenario: Smoke test
    Given I visit "/user"
    Then I should see "Username"
    And I should see "Password"
