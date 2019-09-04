package comparators.dom;

import java.util.ArrayList;
import java.util.List;

import comparators.DomAbstractComparator;

public class DomComparators {

	static List<DomAbstractComparator> domComparators = new ArrayList<DomAbstractComparator>();

	public static List<DomAbstractComparator> getComparators() {
		domComparators.add(new RTEDComparator());
		//domComparators.add(new APTEDComparator());
		domComparators.add(new DOMLevenshteinComparator());
		domComparators.add(new SimHashComparator());
		domComparators.add(new ContentHashComparator());
		return domComparators;
	}

}
