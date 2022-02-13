package net.hypergo.onchat.domain;

import com.fasterxml.jackson.annotation.JsonIdentityInfo;
import com.fasterxml.jackson.annotation.ObjectIdGenerators;
import net.hypergo.onchat.enumerate.MessageType;
import org.hibernate.annotations.DynamicUpdate;
import org.hibernate.annotations.Type;

import javax.persistence.*;
import java.util.Map;
import java.util.StringJoiner;

@Table
@Entity
@DynamicUpdate
@JsonIdentityInfo(generator = ObjectIdGenerators.PropertyGenerator.class, property = "id")
public class ChatRecord extends IdEntity {
    @Column(nullable = false, columnDefinition = "TINYINT(1) UNSIGNED")
    @Enumerated(EnumType.ORDINAL)
    private MessageType type;

    @Type(type = "JSON")
    @Column(columnDefinition = "JSON")
    private Map<String, Object> data;

    @OneToOne(fetch = FetchType.EAGER)
    @JoinColumn(name = "reply_id")
    private ChatRecord reply;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "user_id", nullable = false)
    private User user;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "chatroom_id", nullable = false)
    private Chatroom chatroom;

    public MessageType getType() {
        return type;
    }

    public void setType(MessageType type) {
        this.type = type;
    }

    public Map<String, Object> getData() {
        return data;
    }

    public void setData(Map<String, Object> data) {
        this.data = data;
    }

    public ChatRecord getReply() {
        return reply;
    }

    public void setReply(ChatRecord reply) {
        this.reply = reply;
    }

    public User getUser() {
        return user;
    }

    public void setUser(User user) {
        this.user = user;
    }

    public Chatroom getChatroom() {
        return chatroom;
    }

    public void setChatroom(Chatroom chatroom) {
        this.chatroom = chatroom;
    }

    @Override
    public String toString() {
        return new StringJoiner(", ", ChatRecord.class.getSimpleName() + "[", "]")
                .add("id=" + id)
                .add("type=" + type)
                .add("data=" + data)
                .add("reply=" + reply)
                .add("user=" + user.getId())
                .add("chatroom=" + chatroom.getId())
                .toString();
    }
}
