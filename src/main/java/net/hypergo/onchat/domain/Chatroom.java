package net.hypergo.onchat.domain;

import com.fasterxml.jackson.annotation.JsonIdentityInfo;
import com.fasterxml.jackson.annotation.ObjectIdGenerators;
import net.hypergo.onchat.enumerate.ChatroomType;
import org.hibernate.annotations.DynamicUpdate;

import javax.persistence.*;
import javax.validation.constraints.Size;
import java.util.Collection;
import java.util.StringJoiner;

@Table
@Entity
@DynamicUpdate
@JsonIdentityInfo(generator = ObjectIdGenerators.PropertyGenerator.class, property = "id")
public class Chatroom extends IdEntity {
    @Size(min = 1, max = 15)
    @Column(nullable = false, length = 30)
    private String name;

    @Size(min = 1, max = 256)
    @Column(length = 512)
    private String description;

    @Column(nullable = false)
    private String avatar;

    @Column(nullable = false, columnDefinition = "TINYINT(1) UNSIGNED")
    @Enumerated(EnumType.ORDINAL)
    private ChatroomType type;

    @Column(nullable = false, columnDefinition = "SMALLINT UNSIGNED")
    private Short peopleLimit;

    @OneToMany(mappedBy = "chatroom", fetch = FetchType.LAZY)
    private Collection<ChatMember> chatMembers;

    @OneToMany(mappedBy = "chatroom", fetch = FetchType.LAZY)
    private Collection<ChatRecord> chatRecords;

    @OneToMany(mappedBy = "chatroom", fetch = FetchType.LAZY)
    private Collection<ChatRequest> chatRequests;

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public String getAvatar() {
        return avatar;
    }

    public void setAvatar(String avatar) {
        this.avatar = avatar;
    }

    public ChatroomType getType() {
        return type;
    }

    public void setType(ChatroomType type) {
        this.type = type;
    }

    public Short getPeopleLimit() {
        return peopleLimit;
    }

    public void setPeopleLimit(Short peopleLimit) {
        this.peopleLimit = peopleLimit;
    }

    public Collection<ChatMember> getChatMembers() {
        return chatMembers;
    }

    public void setChatMembers(Collection<ChatMember> chatMembers) {
        this.chatMembers = chatMembers;
    }

    public Collection<ChatRecord> getChatRecords() {
        return chatRecords;
    }

    public void setChatRecords(Collection<ChatRecord> chatRecords) {
        this.chatRecords = chatRecords;
    }

    public Collection<ChatRequest> getChatRequests() {
        return chatRequests;
    }

    public void setChatRequests(Collection<ChatRequest> chatRequests) {
        this.chatRequests = chatRequests;
    }

    @Override
    public String toString() {
        return new StringJoiner(", ", Chatroom.class.getSimpleName() + "[", "]")
                .add("id=" + id)
                .add("name='" + name + "'")
                .add("description='" + description + "'")
                .add("avatar='" + avatar + "'")
                .add("type=" + type)
                .add("peopleLimit=" + peopleLimit)
//                .add("chatMembers=" + chatMembers)
                .toString();
    }
}
