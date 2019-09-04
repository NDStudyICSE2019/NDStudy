package utils;

import static com.google.common.base.Preconditions.checkArgument;

import java.awt.FlowLayout;
import java.awt.Graphics2D;
import java.awt.image.BufferedImage;
import java.io.File;
import java.io.IOException;
import java.text.SimpleDateFormat;
import java.util.Locale;

import javax.imageio.ImageIO;
import javax.swing.ImageIcon;
import javax.swing.JFrame;
import javax.swing.JLabel;

//import com.crawljax.core.state.CandidateElementPosition;
//import com.crawljax.plugins.crawloverview.model.OutPutModel;
//import com.crawljax.plugins.crawloverview.model.State;
import com.fasterxml.jackson.annotation.JsonAutoDetect;
import com.fasterxml.jackson.core.JsonGenerator;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.JsonSerializer;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.SerializationFeature;
import com.fasterxml.jackson.databind.SerializerProvider;
import com.fasterxml.jackson.databind.module.SimpleModule;
import com.fasterxml.jackson.datatype.guava.GuavaModule;
import com.google.common.collect.ImmutableList;

import config.Settings;
//import net.bytebuddy.build.Plugin;

public class ImageUtils {
	//private static final ObjectMapper MAPPER;
	private static final int xAdj = 50;
	private static final int yAdj = 50;
	/*static {
		MAPPER = new ObjectMapper();
		MAPPER.getSerializationConfig().getDefaultVisibilityChecker()
		        .withFieldVisibility(JsonAutoDetect.Visibility.ANY)
		        .withGetterVisibility(JsonAutoDetect.Visibility.NONE)
		        .withSetterVisibility(JsonAutoDetect.Visibility.NONE)
		        .withCreatorVisibility(JsonAutoDetect.Visibility.NONE);
		MAPPER.disable(SerializationFeature.FAIL_ON_EMPTY_BEANS);

		MAPPER.setDateFormat(new SimpleDateFormat("yyyy-MM-dd HH:mm:ss z", Locale.getDefault()));

		MAPPER.registerModule(new GuavaModule());
		SimpleModule testModule = new SimpleModule("Plugin serialiezr");
		testModule.addSerializer(new JsonSerializer<Plugin>() {

			@Override
			public void serialize(Plugin plugin, JsonGenerator jgen,
			        SerializerProvider provider) throws IOException, JsonProcessingException {
				jgen.writeString(plugin.getClass().getSimpleName());
			}

			@Override
			public Class<Plugin> handledType() {
				return Plugin.class;
			}
		});

		MAPPER.registerModule(testModule);

	}*/
	
	public static void displayImage(BufferedImage image) throws IOException {
        ImageIcon icon=new ImageIcon(image);
        JFrame frame=new JFrame();
        frame.setLayout(new FlowLayout());
        frame.setSize(image.getWidth(),image.getHeight());
        JLabel lbl=new JLabel();
        lbl.setIcon(icon);
        frame.add(lbl);
        frame.setVisible(true);
        frame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
	}
	/*
	public static BufferedImage getInterestingImage(BufferedImage originalImage, State state) throws IOException {
		//ImmutableList<CandidateElementPosition> candidateElements = state.getCandidateElements();
		
		//displayImage(originalImage);
		BufferedImage newImage = new BufferedImage(originalImage.getWidth(), originalImage.getHeight(), BufferedImage.TYPE_INT_RGB);
	/*	Graphics2D imageGraphics = newImage.createGraphics();
		for(CandidateElementPosition ce : candidateElements) {
			int x = ce.getLeft(), y= ce.getTop(), w = ce.getWidth(), h = ce.getHeight();
			int x2 = x-xAdj, y2 = y-yAdj, w2= w + xAdj*2, h2 = h + yAdj*2;
			//System.out.println(ce.toString());
			BufferedImage subImage  = originalImage.getSubimage(x2, y2, w2, h2);
			imageGraphics.drawImage(subImage, x2, y2, w2 ,h2, null );
		}
		//displayImage(newImage);
*/		//return newImage;
//	}

/*
	@SuppressWarnings("deprecation")
	public static void main(String args[]) {
		
		modifyAllScreenshots();
	}

	public static void modifyAllScreenshots() {
		String crawlFolderPath = Settings.resourcesFolder + File.separator + Settings.app;
		String targetScreenshotPath = crawlFolderPath + File.separator + "modifiedScreenshots";
		String jsonPath = crawlFolderPath + File.separator + "result.json";
		File screenshotsFolder = new File(targetScreenshotPath);
		if (!screenshotsFolder.exists()) {
			boolean created = screenshotsFolder.mkdir();
			checkArgument(created, "Could not create screenshotsFolder dir");
		}
		
		try {
			OutPutModel result = MAPPER.readValue(new File(jsonPath), OutPutModel.class);
			for(String stateName: result.getStates().keySet()) {
				System.out.println(stateName);
				State state = result.getStates().get(stateName);
				String imageName = stateName + ".png";
				String imagePath = crawlFolderPath + File.separator + "screenshots" + File.separator + imageName;
				BufferedImage image = ImageIO.read(new File(imagePath));
				BufferedImage changed = getInterestingImage(image, state);
				String newImagePath = targetScreenshotPath + File.separator + imageName;
				ImageIO.write(changed, "PNG", new File(newImagePath));
			}
		} catch (IOException e) {
			e.printStackTrace();
		}
	}*/
}
