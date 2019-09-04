package comparators.visual;

import java.util.ArrayList;
import java.util.List;

import comparators.VisualAbstractComparator;

public class VisualComparators {

	static List<VisualAbstractComparator> visualComparators = new ArrayList<VisualAbstractComparator>();

	public static List<VisualAbstractComparator> getComparators() {
		//visualComparators.add(new AverageHashComparator());
		visualComparators.add(new BlockMeanHashComparator());
		//visualComparators.add(new ColorMomentHashComparator());
		//visualComparators.add(new MarrHildrethHashComparator());
		visualComparators.add(new PHashComparator());
		//visualComparators.add(new RadialVarianceHashComparator());
		visualComparators.add(new HistogramComparator());
		visualComparators.add(new PDiffComparator());
		//visualComparators.add(new PixelToPixelComparison());
		visualComparators.add(new SIFTComparator());
		visualComparators.add(new SSIMComparator());
		return visualComparators;
	}

}
