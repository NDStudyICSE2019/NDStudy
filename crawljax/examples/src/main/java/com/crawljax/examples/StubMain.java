package com.crawljax.examples;

import java.io.File;
import java.util.concurrent.TimeUnit;

import com.crawljax.core.CrawljaxRunner;
import com.crawljax.core.configuration.CrawljaxConfiguration;
import com.crawljax.core.configuration.CrawljaxConfiguration.CrawljaxConfigurationBuilder;
import com.crawljax.examples.addressbook.AddressbookCrawlingRules;
import com.crawljax.examples.addressbook.AddressbookRunner;
import com.crawljax.examples.claroline.ClarolineCrawlingRules;
import com.crawljax.examples.claroline.ClarolineRunner;
import com.crawljax.examples.dimeshift.DimeShiftCrawlingRules;
import com.crawljax.examples.dimeshift.DimeShiftRunner;
import com.crawljax.examples.mantisbt.MantisbtCrawlingRules;
import com.crawljax.examples.mantisbt.MantisbtRunner;
import com.crawljax.examples.mrbs.MrbsCrawlingRules;
import com.crawljax.examples.mrbs.MrbsRunner;
import com.crawljax.examples.pagekit.PageKitCrawlingRules;
import com.crawljax.examples.pagekit.PageKitRunner;
import com.crawljax.examples.petclinic.PetclinicCrawlingRules;
import com.crawljax.examples.petclinic.PetclinicRunner;
import com.crawljax.examples.phoenix.PhoenixCrawlingRules;
import com.crawljax.examples.phoenix.PhoenixRunner;
import com.crawljax.examples.ppma.PpmaCrawlingRules;
import com.crawljax.examples.ppma.PpmaRunner;
import com.crawljax.stateabstractions.dom.DOMConfiguration.Mode;
import com.crawljax.stateabstractions.dom.LevenshteinStateVertexFactory;
import com.crawljax.stateabstractions.dom.RTEDStateVertexFactory;
import com.crawljax.stateabstractions.dom.SimHashStateVertexFactory;
import com.crawljax.stateabstractions.dom.TLSHStateVertexFactory;
import com.crawljax.stateabstractions.visual.ColorHistogramStateVertexFactory;
import com.crawljax.stateabstractions.visual.PDiffStateVertexFactory;
import com.crawljax.stateabstractions.visual.SIFTStateVertexFactory;
import com.crawljax.stateabstractions.visual.SSIMStateVertexFactory;
import com.crawljax.stateabstractions.visual.imagehashes.BlockMeanImageHashStateVertexFactory;
import com.crawljax.stateabstractions.visual.imagehashes.PerceptualImageHashStateVertexFactory;

public class StubMain {
	

	 public static void main(String[] args) throws Exception {
		 	if(args.length<3) {
				System.out.println("Incorrect number of paramenters");
				StubMain.printUsage();
			}
		 	
		 	String app = args[0].toLowerCase();
		 	String saf = args[1].toUpperCase();
		 	String URL = null;
			double threshold = -1;
			long runtime = -1;
			int maxStates = -1;
			
			switch(app) {
            case "addressbook":
            	URL = AddressbookRunner.URL;
            	break;
            case "petclinic":
            	URL = PetclinicRunner.URL;
            	break;
            case "claroline":
            	URL = ClarolineRunner.URL;
            	break;
            case "dimeshift":
            	URL = DimeShiftRunner.URL;
            	break;
            case "pagekit":
            	URL = PageKitRunner.URL;
            	break;
            case "phoenix":
            	URL = PhoenixRunner.URL;
            	break;
        	case "ppma":
                URL = PpmaRunner.URL;
                break;
        	case "mrbs":
        		URL = MrbsRunner.URL;
        		break;
        	case "mantisbt":
        		URL = MantisbtRunner.URL;
        		break;
            }
			
			try {
				 runtime = Long.parseLong(args[2]); 
			}catch(Exception Ex) {
				System.out.println("Exception while parsing time string. Please provide a valid time in minutes");
				StubMain.printUsage();
				System.exit(-1);;
			}
			if(args.length >=4) {
				try {
					 threshold = Double.parseDouble(args[3]); 
				}catch(Exception Ex) {
					System.out.println("Exception while parsing threshold string. Please provide a valid float number as threshold for the chosen SAF");
					StubMain.printUsage();
					System.exit(-1);;
				}
			}
			
			if(args.length == 5) {
				try {
					 maxStates = Integer.parseInt(args[4]); 
				}catch(Exception Ex) {
					System.out.println("Exception while parsing maxStates string. Please provide a valid integer as max states for the crawl");
					StubMain.printUsage();
					System.exit(-1);;
				}
			}
			
			run(app, saf, runtime, threshold, maxStates, URL);
		}
		
		public static void run(String app, String saf, long runtime, double threshold, int maxStates, String URL) {
			CrawljaxConfigurationBuilder builder = CrawljaxConfiguration.builderFor(URL);

			System.out.println("*******************************************************************");
			System.out.println(saf);
			switch(app) {
            case "addressbook":
            	AddressbookCrawlingRules.setCrawlingRules(builder);
            	break;
            case "petclinic":
            	PetclinicCrawlingRules.setCrawlingRules(builder);
            	break;
            case "claroline":
            	ClarolineCrawlingRules.setCrawlingRules(builder);
            	break;
            case "dimeshift":
            	DimeShiftCrawlingRules.setCrawlingRules(builder);
            	break;
            case "pagekit":
            	PageKitCrawlingRules.setCrawlingRules(builder);
            	break;
            case "phoenix":
            	PhoenixCrawlingRules.setCrawlingRules(builder);
            	break;
            case "ppma":
            	PpmaCrawlingRules.setCrawlingRules(builder);
            	break;
            case "mrbs":
            	MrbsCrawlingRules.setCrawlingRules(builder);
            	break;
            case "mantisbt":
            	MantisbtCrawlingRules.setCrawlingRules(builder);
            	break;
            }
			
			
			if(maxStates != -1) {
				builder.setMaximumStates(maxStates);
//				runtime=60;
			}
			builder.setMaximumRunTime(runtime, TimeUnit.MINUTES);
			
			File customOuput = new File("out" + File.separator + app + File.separator + app + "_" + saf + "_"+ threshold + "_" + runtime + "mins");
			builder.setOutputDirectory(customOuput);
			CrawljaxRunner crawljax = null;
			
			switch(saf) {
			
				case "DOM_LEVENSHTEIN":
					threshold = 1.0-threshold>=0?(1.0-threshold):0.0;
					builder.setStateVertexFactory(new LevenshteinStateVertexFactory(threshold));
					crawljax= new CrawljaxRunner(builder.build());
					break;
				case "DOM_RTED":
					builder.setStateVertexFactory(new RTEDStateVertexFactory(threshold));
					crawljax= new CrawljaxRunner(builder.build());
					break;
				case "DOM_SIMHASH":
					builder.setStateVertexFactory(new SimHashStateVertexFactory(threshold, Mode.STRIPPED_DOM));
					crawljax= new CrawljaxRunner(builder.build());
					break;
				case "DOM_CONTENTHASH":
					builder.setStateVertexFactory(new TLSHStateVertexFactory(threshold, Mode.ORIGINAL_DOM));
					crawljax= new CrawljaxRunner(builder.build());
					break;
				case "VISUAL_PHASH":
					builder.setStateVertexFactory(new PerceptualImageHashStateVertexFactory(threshold));
					crawljax= new CrawljaxRunner(builder.build());
					break;
				case "VISUAL_PDIFF":
					builder.setStateVertexFactory(new PDiffStateVertexFactory(threshold));
					crawljax= new CrawljaxRunner(builder.build());
					break;
				case "VISUAL_BLOCKHASH":
					builder.setStateVertexFactory(new BlockMeanImageHashStateVertexFactory(threshold));
					crawljax= new CrawljaxRunner(builder.build());
					break;
				case "VISUAL_HYST":
					builder.setStateVertexFactory(new ColorHistogramStateVertexFactory(threshold));
					crawljax= new CrawljaxRunner(builder.build());
					break;
				case "VISUAL_SSIM":
					builder.setStateVertexFactory(new SSIMStateVertexFactory(threshold));
					crawljax= new CrawljaxRunner(builder.build());
					break;
				case "VISUAL_SIFT":
					builder.setStateVertexFactory(new SIFTStateVertexFactory(threshold));
					crawljax= new CrawljaxRunner(builder.build());
					break;
				default:
					crawljax=new CrawljaxRunner(builder.build());
					
			}
			if(crawljax!=null)
				crawljax.call();
		}
	 
	public static void printUsage() {
		System.out.println("Usage : "
				+ "runner <app{addressbook, petclinic, phonecat, claroline, dimeshift, pagekit, phoenix, retroboard, splittypie, collabtive, ppma, mrbs, mantisbt}> \n"
				+ "<saf{dom_levenshtein, dom_rted, dom_simhash, dom_contenthash, visual_pdiff, visual_phash, visual_Blockhash, visual_SSIM, visual_SIFT, visual_hyst, default}> \n"
				+ "<runtime(mins)> \n"
				+ "<opt:threshold> \n"
				+ "<opt:maxStates> \n");
	}
}
