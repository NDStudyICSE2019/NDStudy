package comparators;

import org.opencv.core.Mat;

public abstract class VisualAbstractComparator {

	public abstract double computeDistance();

	public abstract String getName();

	public abstract void setPage1(Mat imread);

	public abstract void setPage2(Mat imread);

	public void setPage1(String file1) {}

	public void setPage2(String file2) {}

}
