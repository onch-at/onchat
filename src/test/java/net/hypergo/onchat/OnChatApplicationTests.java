package net.hypergo.onchat;

import net.hypergo.onchat.domain.ChatSession;
import net.hypergo.onchat.domain.User;
import net.hypergo.onchat.domain.UserInfo;
import net.hypergo.onchat.enumerate.ChatSessionType;
import net.hypergo.onchat.enumerate.Mood;
import net.hypergo.onchat.repository.ChatSessionRepository;
import net.hypergo.onchat.repository.UserInfoRepository;
import net.hypergo.onchat.repository.UserRepository;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;

import java.util.Arrays;
import java.util.HashMap;
import java.util.Map;

@SpringBootTest
class OnChatApplicationTests {

    @Test
//    @Transactional
    void contextLoads(
            @Autowired UserRepository repository,
            @Autowired ChatSessionRepository chatSessionRepository
    ) {
        User user = new User();
        user.setUsername("HyperLife1119");
        user.setPassword("***");
        user.setEmail("hyperlife1119@qq.com");

        UserInfo userInfo = new UserInfo();
        userInfo.setAvatar("http");
        userInfo.setBackgroundImage("http");
        userInfo.setLoginTime(1L);
        userInfo.setMood(Mood.JOY);
        userInfo.setNickname("hl");

        user.setInfo(userInfo);

        ChatSession chatSession = new ChatSession();
        Map<String, Object> map = new HashMap<>();
        map.put("id", 1);
        chatSession.setData(map);
        chatSession.setType(ChatSessionType.CHATROOM);
        chatSession.setUser(user);

        user.setChatSessions(Arrays.asList(chatSession));

        repository.save(user);
    }

    @Test
    void findUserByUserInfo(@Autowired UserInfoRepository repository) {
        System.out.println(repository.findById(1L).get().getUser());
    }

    @Test
    void findUserInfoByUser(@Autowired UserRepository repository) {
        System.out.println(repository.findById(1L).get().getInfo());
    }

}
