package com.crawljax.examples.phoenix;

import java.io.IOException;
import java.util.concurrent.TimeUnit;

import com.crawljax.core.CrawlSession;
import com.crawljax.core.CrawljaxRunner;
import com.crawljax.core.configuration.CrawljaxConfiguration;
import com.crawljax.core.configuration.CrawljaxConfiguration.CrawljaxConfigurationBuilder;
import com.crawljax.core.state.DefaultStateVertexFactory;
import com.crawljax.examples.addressbook.AddressbookCrawlingRules;
import com.crawljax.stateabstractions.dom.LevenshteinStateVertexFactory;
import com.crawljax.stateabstractions.dom.RTEDStateVertexFactory;
import com.crawljax.stateabstractions.dom.SimHashStateVertexFactory;
import com.crawljax.stateabstractions.dom.TLSHStateVertexFactory;
import com.crawljax.stateabstractions.dom.DOMConfiguration.Mode;
import com.crawljax.stateabstractions.visual.PDiffStateVertexFactory;
import com.crawljax.stateabstractions.visual.SIFTStateVertexFactory;
import com.crawljax.stateabstractions.visual.SSIMStateVertexFactory;
//import com.crawljax.examples.StateEquivalenceExamples;
import com.crawljax.plugins.crawloverview.CrawlOverview;
//import com.crawljax.visual.ColorHystogramStateVertexFactory;
//import com.crawljax.visual.VisHashStateVertexFactory;
//import com.crawljax.visual.hashes.BMHash;
//import com.crawljax.visual.hashes.CMHash;
//import com.crawljax.visual.hashes.PHash2;

/**
 * Crawljax runner for the Angular Phonecat web application. The crawl will
 * produce output using the {@link CrawlOverview} plugin.
 * 
 * @author astocco
 */
public final class PhoenixRunner {

	static final long WAIT_TIME_AFTER_EVENT = 1000;
	static final long WAIT_TIME_AFTER_RELOAD = 1000;
	public static final String URL = "http://localhost:4000";

	/**
	 * Run this method to start the crawl.
	 * 
	 * @throws IOException
	 *             when the output folder cannot be created or emptied.
	 */
	public static void main(String[] args) throws IOException {

		CrawljaxConfigurationBuilder builder = CrawljaxConfiguration.builderFor(URL);

		PhoenixCrawlingRules.setCrawlingRules(builder);

		/* default state abstraction. */
//		
//		builder.setStateVertexFactory(new DefaultStateVertexFactory());
//		CrawljaxRunner defaultCrawljax = new CrawljaxRunner(builder.build());
//		CrawlSession defaultSession = defaultCrawljax.call();
//		
		/*
		builder.setStateVertexFactory(new VisHashStateVertexFactory(new CMHash()));
		CrawljaxRunner cmHashCrawljax = new CrawljaxRunner(builder.build());
		cmHashCrawljax.call();*/
		
		/*
		builder.setStateVertexFactory(new VisHashStateVertexFactory(new PHash2(0.1)));
		CrawljaxRunner pHashCrawljax = new CrawljaxRunner(builder.build());
		pHashCrawljax.call();*/
		
		/*
		builder.setStateVertexFactory(new LevensteinStateVertexFactory());
		CrawljaxRunner levensteinCrawljax = new CrawljaxRunner(builder.build());
		levensteinCrawljax.call();
	*/
		
		builder.setStateVertexFactory(new RTEDStateVertexFactory());
		CrawljaxRunner RTEDCrawljax = new CrawljaxRunner(builder.build());
		RTEDCrawljax.call();
		
		/*
		builder.setStateVertexFactory(new ColorHystogramStateVertexFactory());
		CrawljaxRunner chystCrawljax = new CrawljaxRunner(builder.build());
		chystCrawljax.call();*/

		/*builder.setStateVertexFactory(new VisHashStateVertexFactory(new BMHash(0.0)));
		CrawljaxRunner bmHashCrawljax = new CrawljaxRunner(builder.build());
		bmHashCrawljax.call();*/
		
		/*builder = CrawljaxConfiguration.builderFor(URL);
		PhonecatCrawlingRules.setCrawlingRules(builder);
		builder.setStateVertexFactory(new TLSHStateVertexFactory(0.0, Mode.STRIPPEDDOM));
		CrawljaxRunner TLSHCrawljax = new CrawljaxRunner(builder.build());
		TLSHCrawljax.call();
		TLSHCrawljax.stop();
		
		builder = CrawljaxConfiguration.builderFor(URL);
		PhonecatCrawlingRules.setCrawlingRules(builder);
		builder.setStateVertexFactory(new TLSHStateVertexFactory(0.025, Mode.STRIPPEDDOM));
		CrawljaxRunner TLSHCrawljax0025 = new CrawljaxRunner(builder.build());
		TLSHCrawljax0025.call();
		TLSHCrawljax0025.stop();*/
//		
//		builder = CrawljaxConfiguration.builderFor(URL);
//		PetclinicCrawlingRules.setCrawlingRules(builder);
//		builder.setStateVertexFactory(new TLSHStateVertexFactory(0.05, Mode.STRIPPED_DOM));
//		CrawljaxRunner TLSHCrawljax005 = new CrawljaxRunner(builder.build());
//		TLSHCrawljax005.call();
//		TLSHCrawljax005.stop();
		
		/*builder = CrawljaxConfiguration.builderFor(URL);
		PhonecatCrawlingRules.setCrawlingRules(builder);
		builder.setStateVertexFactory(new TLSHStateVertexFactory(0.1, Mode.STRIPPEDDOM));
		CrawljaxRunner TLSHCrawljax01 = new CrawljaxRunner(builder.build());
		TLSHCrawljax01.call();
		TLSHCrawljax01.stop();*/
		
//		builder = CrawljaxConfiguration.builderFor(URL);
//		PetclinicCrawlingRules.setCrawlingRules(builder);
//		builder.setStateVertexFactory(new SSIMStateVertexFactory());
//		CrawljaxRunner SSIMCrawljax = new CrawljaxRunner(builder.build());
//		SSIMCrawljax.call();
//		SSIMCrawljax.stop();
		
//
//		builder = CrawljaxConfiguration.builderFor(URL);
//		DimeShiftCrawlingRules.setCrawlingRules(builder);
//		if(args.length == 1) {
//
//			builder.setMaximumRunTime(Long.parseLong(args[0]), TimeUnit.MINUTES);
//		}
//		builder.setStateVertexFactory(new SIFTStateVertexFactory(-1));
//		CrawljaxRunner SIFTCrawljax = new CrawljaxRunner(builder.build());
//		SIFTCrawljax.call();
//		SIFTCrawljax.stop();
//	
//
//		builder = CrawljaxConfiguration.builderFor(URL);
//		PetclinicCrawlingRules.setCrawlingRules(builder);
//		if(args.length == 1) {
//
//			builder.setMaximumRunTime(Long.parseLong(args[0]), TimeUnit.MINUTES);
//		}
//		builder.setStateVertexFactory(new PDiffStateVertexFactory());
//		CrawljaxRunner PDiffCrawljax = new CrawljaxRunner(builder.build());
//		PDiffCrawljax.call();
//		PDiffCrawljax.stop();
//		
//		System.out.println("*******************************************************************");
//		System.out.println("SimHash");
//		builder = CrawljaxConfiguration.builderFor(URL);
//		if(args.length == 1) {
//
//			builder.setMaximumRunTime(Long.parseLong(args[0]), TimeUnit.MINUTES);
//		}
//		PetclinicCrawlingRules.setCrawlingRules(builder);
//		builder.setStateVertexFactory(new SimHashStateVertexFactory(Mode.STRIPPED_DOM));
//		CrawljaxRunner SimHashCrawljax = new CrawljaxRunner(builder.build());
//		SimHashCrawljax.call();
//		
//		
		
	}

}
