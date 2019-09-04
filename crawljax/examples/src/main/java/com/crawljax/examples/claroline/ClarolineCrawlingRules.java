package com.crawljax.examples.claroline;

import java.util.concurrent.TimeUnit;

import com.crawljax.browser.EmbeddedBrowser.BrowserType;
import com.crawljax.core.configuration.BrowserConfiguration;
import com.crawljax.core.configuration.CrawlRules.CrawlPriorityMode;
import com.crawljax.core.configuration.CrawlRules.FormFillMode;
import com.crawljax.core.configuration.CrawljaxConfiguration.CrawljaxConfigurationBuilder;
import com.crawljax.core.configuration.InputSpecification;
import com.crawljax.plugins.crawloverview.CrawlOverview;

public class ClarolineCrawlingRules {

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

		builder.crawlRules().setFormFillMode(FormFillMode.RANDOM);

		builder.crawlRules().setCrawlPriorityMode(CrawlPriorityMode.NORMAL);
		builder.crawlRules().setCrawlNearDuplicates(true);
		builder.crawlRules().setDelayNearDuplicateCrawling(false);
//		builder.crawlRules().click("a", "button", "input");

		/* do not click these. */
		builder.crawlRules().dontClick("a").withText("php-addressbook");
		builder.crawlRules().dontClick("a").withText("v9.0.0.1");
//		builder.crawlRules().dontClick("a").withText("map");
//		builder.crawlRules().dontClick("a").withText("export");
//		builder.crawlRules().dontClick("a").withText("import");

		/* set timeouts. */
		builder.setUnlimitedCrawlDepth();
		// builder.setMaximumRunTime(30, TimeUnit.MINUTES);
		builder.setUnlimitedStates();
		//builder.setMaximumStates(1500);
		//builder.setUnlimitedRuntime();
		builder.setMaximumRunTime(60, TimeUnit.MINUTES);
		builder.crawlRules().waitAfterReloadUrl(ClarolineRunner.WAIT_TIME_AFTER_RELOAD, TimeUnit.MILLISECONDS);
		builder.crawlRules().waitAfterEvent(ClarolineRunner.WAIT_TIME_AFTER_EVENT, TimeUnit.MILLISECONDS);

		/* set browser. */
		builder.setBrowserConfig(new BrowserConfiguration(BrowserType.CHROME, 1));

		/* input data. */
		builder.crawlRules().setInputSpec(ClarolineCrawlingRules.clarolineInputSpecification());

		/* CrawlOverview. */
		builder.addPlugin(new CrawlOverview());
		builder.addPlugin(new ClarolineCleanup());
	}

	/**
	 * List of inputs to crawl the AddressBook application.
	 */
	static InputSpecification clarolineInputSpecification() {

		InputSpecification inputClaroline = new InputSpecification();

		ClarolineForms.login(inputClaroline);
		//ClarolineForms.newUser(inputAddressBook);
		ClarolineForms.newCourse(inputClaroline);
		return inputClaroline;
	}

}
