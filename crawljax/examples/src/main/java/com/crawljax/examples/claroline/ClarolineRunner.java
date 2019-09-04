package com.crawljax.examples.claroline;

import java.io.IOException;
import java.util.concurrent.TimeUnit;

import com.crawljax.core.CrawljaxRunner;
import com.crawljax.core.configuration.CrawljaxConfiguration;
import com.crawljax.core.configuration.CrawljaxConfiguration.CrawljaxConfigurationBuilder;
import com.crawljax.plugins.crawloverview.CrawlOverview;
import com.crawljax.stateabstractions.dom.DOMConfiguration.Mode;
import com.crawljax.stateabstractions.dom.SimHashStateVertexFactory;
import com.crawljax.stateabstractions.visual.PDiffStateVertexFactory;

/**
 * Crawljax runner for the AddressBook web application. The crawl will produce
 * output using the {@link CrawlOverview} plugin.
 * 
 * @author astocco
 */
public final class ClarolineRunner {

	static final long WAIT_TIME_AFTER_EVENT = 500;
	static final long WAIT_TIME_AFTER_RELOAD = 500;
	public static final String URL = "http://localhost:8888/claroline/claroline-1.11.10/";
	//private static final String URL = "http://localhost:8888/addressbook/addressbook-mod/addressbook/index.php";

	//private static final String URL = "http://www.facebook.com";

	/**
	 * Run this method to start the crawl.
	 * 
	 * @throws IOException
	 *             when the output folder cannot be created or emptied.
	 */
	public static void main(String[] args) throws IOException {

		CrawljaxConfigurationBuilder builder = CrawljaxConfiguration.builderFor(URL);
		
		ClarolineCrawlingRules.setCrawlingRules(builder);

		/* default state abstraction. */
		/*builder.setStateVertexFactory(new DefaultStateVertexFactory());
		CrawljaxRunner defaultCrawljax = new CrawljaxRunner(builder.build());
		defaultCrawljax.call();*/
		
		/*builder.setStateVertexFactory(new VisHashStateVertexFactory(new PHash2(0.0)));
		CrawljaxRunner phashCrawljax = new CrawljaxRunner(builder.build());
		phashCrawljax.call();*/
		/*
		builder.setStateVertexFactory(new VisHashStateVertexFactory(new BMHash(0.05)));
		CrawljaxRunner bmhashCrawljax = new CrawljaxRunner(builder.build());
		bmhashCrawljax.call();*/
		
		/*builder.setStateVertexFactory(new TLSHStateVertexFactory(0.025, Mode.STRUCTURE));
		CrawljaxRunner TLSHCrawljax = new CrawljaxRunner(builder.build());
		TLSHCrawljax.call();
		*/
		/*builder.setStateVertexFactory(new ColorHystogramStateVertexFactory());
		CrawljaxRunner chystCrawljax = new CrawljaxRunner(builder.build());
		chystCrawljax.call();
		*/
		/*
		builder.setStateVertexFactory(new VisHashStateVertexFactory(new CMHash()));
		CrawljaxRunner cmHashCrawljax = new CrawljaxRunner(builder.build());
		cmHashCrawljax.call();
		//System.out.println("Default crawl: " + StateEquivalenceExamples.getNumberOfStatesFromCrawlSession(defaultSession));
*/
		
		/*builder.setStateVertexFactory(new LevensteinStateVertexFactory(1.0));
		CrawljaxRunner levensteinCrawljax = new CrawljaxRunner(builder.build());
		levensteinCrawljax.call();*/
	
		
/*
		builder.setStateVertexFactory(new RTEDStateVertexFactory(0.0));
		CrawljaxRunner RTEDCrawljax = new CrawljaxRunner(builder.build());
		RTEDCrawljax.call();
	*/
		//System.out.println("Default crawl: " + StateEquivalenceExamples.getNumberOfStatesFromCrawlSession(defaultSession));

		/* P-hash. */
		/*builder.setStateVertexFactory(new PHashStateVertexFactory());
		CrawljaxRunner phashCrawljax = new CrawljaxRunner(builder.build());
		CrawlSession phashSession = phashCrawljax.call();*/
		//System.out.println("PHash crawl: " + StateEquivalenceExamples.getNumberOfStatesFromCrawlSession(phashSession));


//		builder = CrawljaxConfiguration.builderFor(URL);
//		ClarolineCrawlingRules.setCrawlingRules(builder);
//		builder.setStateVertexFactory(new SSIMStateVertexFactory());
//		CrawljaxRunner SSIMCrawljax = new CrawljaxRunner(builder.build());
//		SSIMCrawljax.call();
//		SSIMCrawljax.stop();
//		
//
//		builder = CrawljaxConfiguration.builderFor(URL);
//		ClarolineCrawlingRules.setCrawlingRules(builder);
//		if(args.length == 1) {
//
//			builder.setMaximumRunTime(Long.parseLong(args[0]), TimeUnit.MINUTES);
//		}
//		builder.setStateVertexFactory(new SIFTStateVertexFactory());
//		CrawljaxRunner SIFTCrawljax = new CrawljaxRunner(builder.build());
//		SIFTCrawljax.call();
//		SIFTCrawljax.stop();
//	

		builder = CrawljaxConfiguration.builderFor(URL);
		ClarolineCrawlingRules.setCrawlingRules(builder);
		if(args.length == 1) {

			builder.setMaximumRunTime(Long.parseLong(args[0]), TimeUnit.MINUTES);
		}
		builder.setStateVertexFactory(new PDiffStateVertexFactory(-1));
		CrawljaxRunner PDiffCrawljax = new CrawljaxRunner(builder.build());
		PDiffCrawljax.call();
		PDiffCrawljax.stop();
		
		
		
		System.out.println("*******************************************************************");
		System.out.println("SimHash");
		builder = CrawljaxConfiguration.builderFor(URL);
		ClarolineCrawlingRules.setCrawlingRules(builder);
		if(args.length == 1) {

			builder.setMaximumRunTime(Long.parseLong(args[0]), TimeUnit.MINUTES);
		}
		builder.setStateVertexFactory(new SimHashStateVertexFactory(Mode.STRIPPED_DOM));
		CrawljaxRunner SimHashCrawljax = new CrawljaxRunner(builder.build());
		SimHashCrawljax.call();
		
	}

}
