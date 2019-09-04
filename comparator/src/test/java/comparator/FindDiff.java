package comparator;


import java.io.File;
import java.io.IOException;
import java.nio.charset.Charset;

import org.apache.commons.io.FileUtils;
import org.apache.commons.lang.StringUtils;

import comparators.dom.RTEDComparator;
import utils.DomUtils;

public class FindDiff {
	public static void main(String args[]) {
		RTEDComparator rtedComparator = new RTEDComparator();

		rtedComparator.setPage1("src/test/resources/doms/index.html");
		rtedComparator.setPage2("src/test/resources/doms/index_old.html");

		double distance = rtedComparator.computeDistance();
		System.out.println("RTED (state1, state2): " + distance);
		
		String dom1;
		try {
			dom1 = FileUtils.readFileToString(new File("src/test/resources/doms/state6.html"),  Charset.defaultCharset());
			String dom2 = FileUtils.readFileToString(new File("src/test/resources/doms/state6_old.html"),  Charset.defaultCharset());;
			dom1 = DomUtils.getStrippedDom(dom1);
			dom1 = DomUtils.removeHiddenInputs(dom1);
//			dom1 = DomUtils.removeElementsUnderXpath(dom1, "//*[@id=\"systemmsg\"]");
			dom2 = DomUtils.getStrippedDom(dom2);
			dom2 = DomUtils.removeHiddenInputs(dom2);
//			dom2 = DomUtils.removeElementsUnderXpath(dom2, "//*[@id=\"systemmsg\"]");
//			dom1= DomUtils.getDomWithoutHead(dom1);
//			dom2 = DomUtils.getDomWithoutHead(dom2);
//			
			
//			System.out.println(dom1);
//			
//			
//			System.out.println();
//			System.out.println("******************************************");
//			System.out.println(dom2);
//			
//			
//			System.out.println();
			System.out.println("******************************************");
			System.out.println();
			System.out.println(StringUtils.difference(dom1, dom2));
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		
	}
}
