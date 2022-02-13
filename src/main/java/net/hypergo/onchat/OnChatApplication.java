package net.hypergo.onchat;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.data.jpa.repository.config.EnableJpaAuditing;

@SpringBootApplication
@EnableJpaAuditing
public class OnChatApplication {

    public static void main(String[] args) {
        SpringApplication.run(OnChatApplication.class, args);
    }

}
