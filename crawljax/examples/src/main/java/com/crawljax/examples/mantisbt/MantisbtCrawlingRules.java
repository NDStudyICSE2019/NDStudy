package com.crawljax.examples.mantisbt;

import java.util.concurrent.TimeUnit;

import com.crawljax.browser.EmbeddedBrowser.BrowserType;
import com.crawljax.core.configuration.BrowserConfiguration;
import com.crawljax.core.configuration.CrawlRules.FormFillMode;
import com.crawljax.core.configuration.CrawljaxConfiguration.CrawljaxConfigurationBuilder;
import com.crawljax.core.configuration.InputSpecification;
import com.crawljax.plugins.crawloverview.CrawlOverview;

public class MantisbtCrawlingRules {

	/**
	 * List of crawling rules for the AddressBook application.
	 */
	public static void setCrawlingRules(CrawljaxConfigurationBuilder builder) {

		/* crawling rules. */
		builder.crawlRules().clickElementsInRandomOrder(false);
		builder.crawlRules().clickDefaultElements();
		builder.crawlRules().crawlHiddenAnchors(true);
		builder.crawlRules().crawlFrames(false);
		builder.crawlRules().clickOnce(false);
//		builder.crawlRules().addEventType(EventType.hover);

		builder.crawlRules().setFormFillMode(FormFillMode.RANDOM);

//		builder.crawlRules().click("a", "button", "input");

		/* do not click these. */
		builder.crawlRules().dontClick("a").underXPath("/html/body/table[1]/tbody[1]/tr[1]/td[3]/a");
//		builder.crawlRules().dontClick("a").withText("Main");
//		builder.crawlRules().dontClick("a").withText("map");
//		builder.crawlRules().dontClick("a").withText("export");
//		builder.crawlRules().dontClick("a").withText("import");
		
		//builder.crawlRules().setCrawlPriorityMode(CrawlPriorityMode.OLDEST_FIRST);

		/* set timeouts. */
		builder.setUnlimitedCrawlDepth();
		// builder.setMaximumRunTime(30, TimeUnit.MINUTES);
		builder.setUnlimitedStates();
		//builder.setMaximumStates(150);
		//builder.setUnlimitedRuntime();
		builder.setMaximumRunTime(5, TimeUnit.MINUTES);
		builder.crawlRules().waitAfterReloadUrl(MantisbtRunner.WAIT_TIME_AFTER_RELOAD, TimeUnit.MILLISECONDS);
		builder.crawlRules().waitAfterEvent(MantisbtRunner.WAIT_TIME_AFTER_EVENT, TimeUnit.MILLISECONDS);

		/* set browser. */
		builder.setBrowserConfig(new BrowserConfiguration(BrowserType.CHROME, 1));

		/* input data. */
		builder.crawlRules().setInputSpec(MantisbtCrawlingRules.collabtiveInputSpecification());

		/* CrawlOverview. */
		builder.addPlugin(new CrawlOverview());
	}

	/**
	 * List of inputs to crawl the AddressBook application.
	 */
	static InputSpecification collabtiveInputSpecification() {

		InputSpecification inputCollabtive= new InputSpecification();

		MantisbtForms.login(inputCollabtive);
//		CollabtiveForms.newEntry(inputAddressBook);
//		CollabtiveForms.newUser(inputAddressBook);

		return inputCollabtive;
	}

}
