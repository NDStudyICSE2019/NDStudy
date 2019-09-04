package com.crawljax.examples.claroline;

import java.util.List;

import org.openqa.selenium.By;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;

import com.crawljax.core.CrawlerContext;
import com.crawljax.core.plugin.OnUrlFirstLoadPlugin;

public class ClarolineCleanup implements OnUrlFirstLoadPlugin {
	
	

	@Override
	public void onUrlFirstLoad(CrawlerContext context) {
		WebDriver driver = context.getBrowser().getWebDriver();
		String url = driver.getCurrentUrl();
		// Clean addressbook case : selenium test case 
		
		// login
		driver.findElement(By.id("login")).sendKeys("astocco");
		driver.findElement(By.id("password")).sendKeys("password");
		driver.findElement(By.xpath("//*[@id=\"loginBox\"]/form/fieldset/button")).click();
		
		try {
			Thread.sleep(2000);
		} catch (InterruptedException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		String unregURL = ClarolineRunner.URL + "claroline/auth/courses.php?cmd=exUnreg&course=SE123";
		driver.get(unregURL);
		try {
			Thread.sleep(1000);
		} catch (InterruptedException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		String logOutURL = ClarolineRunner.URL + "index.php?logout=true";
		driver.get(logOutURL);
		try {
			Thread.sleep(2000);
		} catch (InterruptedException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		
		driver.get(ClarolineRunner.URL);
		
		
	}

}
