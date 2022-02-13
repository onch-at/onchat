package net.hypergo.onchat.domain;

import com.fasterxml.jackson.annotation.JsonIdentityInfo;
import com.fasterxml.jackson.annotation.ObjectIdGenerators;
import net.hypergo.onchat.enumerate.Constellation;
import net.hypergo.onchat.enumerate.Mood;
import org.hibernate.annotations.DynamicUpdate;

import javax.persistence.*;
import javax.validation.constraints.Size;
import java.util.StringJoiner;

@Table
@Entity
@DynamicUpdate
@JsonIdentityInfo(generator = ObjectIdGenerators.PropertyGenerator.class, property = "id")
public class UserInfo extends IdEntity {
    @Size(min = 1, max = 15)
    @Column(nullable = false, length = 30)
    private String nickname;

    @Size(min = 1, max = 128)
    @Column(length = 200)
    private String signature;

    @Column(nullable = false, columnDefinition = "TINYINT(1) UNSIGNED")
    @Enumerated(EnumType.ORDINAL)
    private Mood mood;

    @Column(columnDefinition = "BIGINT UNSIGNED")
    private Long birthday;

    @Column(columnDefinition = "TINYINT(1) UNSIGNED")
    @Enumerated(EnumType.ORDINAL)
    private Constellation constellation;

    @Column(nullable = false)
    private String avatar;

    @Column(nullable = false)
    private String backgroundImage;

    @Column(nullable = false, columnDefinition = "BIGINT UNSIGNED")
    private Long loginTime;

    @OneToOne(mappedBy = "info", fetch = FetchType.LAZY)
    private User user;

    public String getNickname() {
        return nickname;
    }

    public void setNickname(String nickname) {
        this.nickname = nickname;
    }

    public String getSignature() {
        return signature;
    }

    public void setSignature(String signature) {
        this.signature = signature;
    }

    public Mood getMood() {
        return mood;
    }

    public void setMood(Mood mood) {
        this.mood = mood;
    }

    public Long getBirthday() {
        return birthday;
    }

    public void setBirthday(Long birthday) {
        this.birthday = birthday;
    }

    public Constellation getConstellation() {
        return constellation;
    }

    public void setConstellation(Constellation constellation) {
        this.constellation = constellation;
    }

    public String getAvatar() {
        return avatar;
    }

    public void setAvatar(String avatar) {
        this.avatar = avatar;
    }

    public String getBackgroundImage() {
        return backgroundImage;
    }

    public void setBackgroundImage(String backgroundImage) {
        this.backgroundImage = backgroundImage;
    }

    public Long getLoginTime() {
        return loginTime;
    }

    public void setLoginTime(Long loginTime) {
        this.loginTime = loginTime;
    }

    public User getUser() {
        return user;
    }

    public void setUser(User user) {
        this.user = user;
    }

    @Override
    public String toString() {
        return new StringJoiner(", ", UserInfo.class.getSimpleName() + "[", "]")
                .add("id=" + id)
                .add("nickname='" + nickname + "'")
                .add("signature='" + signature + "'")
                .add("mood=" + mood)
                .add("birthday=" + birthday)
                .add("constellation=" + constellation)
                .add("avatar='" + avatar + "'")
                .add("backgroundImage='" + backgroundImage + "'")
                .add("loginTime=" + loginTime)
                .toString();
    }
}
