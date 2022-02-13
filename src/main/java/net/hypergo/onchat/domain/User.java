package net.hypergo.onchat.domain;

import com.fasterxml.jackson.annotation.JsonIdentityInfo;
import com.fasterxml.jackson.annotation.ObjectIdGenerators;
import org.hibernate.annotations.DynamicUpdate;

import javax.persistence.*;
import javax.validation.constraints.Email;
import javax.validation.constraints.Size;
import java.util.Collection;
import java.util.StringJoiner;

@Table
@Entity
@DynamicUpdate
@JsonIdentityInfo(generator = ObjectIdGenerators.PropertyGenerator.class, property = "id")
public class User extends IdEntity {
    @Size(min = 5, max = 15)
    @Column(unique = true, nullable = false, length = 30)
    private String username;

    @Column(nullable = false)
    private String password;

    @Email
    @Column(unique = true, nullable = false)
    private String email;

    @Column(unique = true, columnDefinition = "CHAR(11)")
    private String telephone;

    @OneToOne(cascade = CascadeType.ALL, fetch = FetchType.LAZY)
    @PrimaryKeyJoinColumn
    private UserInfo info;

    @OneToMany(mappedBy = "user", fetch = FetchType.LAZY)
    private Collection<ChatMember> chatMembers;

    @OneToMany(mappedBy = "user", cascade = CascadeType.ALL, fetch = FetchType.LAZY)
    private Collection<ChatSession> chatSessions;

    @OneToMany(mappedBy = "requester", fetch = FetchType.LAZY)
    private Collection<FriendRequest> sendFriendRequests;

    @OneToMany(mappedBy = "target", fetch = FetchType.LAZY)
    private Collection<FriendRequest> receiveChatRequests;

    @OneToMany(mappedBy = "requester", fetch = FetchType.LAZY)
    private Collection<ChatRequest> chatRequests;

    public String getUsername() {
        return username;
    }

    public void setUsername(String username) {
        this.username = username;
    }

    public String getPassword() {
        return password;
    }

    public void setPassword(String password) {
        this.password = password;
    }

    public String getEmail() {
        return email;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    public String getTelephone() {
        return telephone;
    }

    public void setTelephone(String telephone) {
        this.telephone = telephone;
    }

    public UserInfo getInfo() {
        return info;
    }

    public void setInfo(UserInfo info) {
        this.info = info;
    }

    public Collection<ChatMember> getChatMembers() {
        return chatMembers;
    }

    public void setChatMembers(Collection<ChatMember> chatMembers) {
        this.chatMembers = chatMembers;
    }

    public Collection<ChatSession> getChatSessions() {
        return chatSessions;
    }

    public void setChatSessions(Collection<ChatSession> chatSessions) {
        this.chatSessions = chatSessions;
    }

    public Collection<FriendRequest> getSendFriendRequests() {
        return sendFriendRequests;
    }

    public void setSendFriendRequests(Collection<FriendRequest> sendFriendRequests) {
        this.sendFriendRequests = sendFriendRequests;
    }

    public Collection<FriendRequest> getReceiveChatRequests() {
        return receiveChatRequests;
    }

    public void setReceiveChatRequests(Collection<FriendRequest> receiveChatRequests) {
        this.receiveChatRequests = receiveChatRequests;
    }

    public Collection<ChatRequest> getChatRequests() {
        return chatRequests;
    }

    public void setChatRequests(Collection<ChatRequest> chatRequests) {
        this.chatRequests = chatRequests;
    }

    @Override
    public String toString() {
        return new StringJoiner(", ", User.class.getSimpleName() + "[", "]")
                .add("id=" + id)
                .add("username='" + username + "'")
                .add("password='" + password + "'")
                .add("email='" + email + "'")
                .add("telephone='" + telephone + "'")
                .toString();
    }
}
