package net.hypergo.onchat.domain;

import com.fasterxml.jackson.annotation.JsonIdentityInfo;
import com.fasterxml.jackson.annotation.ObjectIdGenerators;
import net.hypergo.onchat.enumerate.ChatSessionType;
import org.hibernate.annotations.ColumnDefault;
import org.hibernate.annotations.DynamicUpdate;
import org.hibernate.annotations.Type;

import javax.persistence.*;
import java.util.Map;
import java.util.StringJoiner;

@Table
@Entity
@DynamicUpdate
@JsonIdentityInfo(generator = ObjectIdGenerators.PropertyGenerator.class, property = "id")
public class ChatSession extends IdEntity {
    @Column(nullable = false, columnDefinition = "TINYINT(1) UNSIGNED")
    @Enumerated(EnumType.ORDINAL)
    private ChatSessionType type;

    @Type(type = "JSON")
    @Column(columnDefinition = "JSON")
    private Map<String, Object> data;

    @Column(nullable = false, columnDefinition = "TINYINT(1) UNSIGNED")
    @ColumnDefault("0")
    private Short unread = 0;

    @Column(nullable = false, columnDefinition = "BOOLEAN")
    @ColumnDefault("TRUE")
    private Boolean visible = true;

    @Column(nullable = false, columnDefinition = "BOOLEAN")
    @ColumnDefault("FALSE")
    private Boolean sticky = false;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "user_id", nullable = false)
    private User user;

    public ChatSessionType getType() {
        return type;
    }

    public void setType(ChatSessionType type) {
        this.type = type;
    }

    public Map<String, Object> getData() {
        return data;
    }

    public void setData(Map<String, Object> data) {
        this.data = data;
    }

    public Short getUnread() {
        return unread;
    }

    public void setUnread(Short unread) {
        this.unread = unread;
    }

    public Boolean getVisible() {
        return visible;
    }

    public void setVisible(Boolean visible) {
        this.visible = visible;
    }

    public Boolean getSticky() {
        return sticky;
    }

    public void setSticky(Boolean sticky) {
        this.sticky = sticky;
    }

    public User getUser() {
        return user;
    }

    public void setUser(User user) {
        this.user = user;
    }

    @Override
    public String toString() {
        return new StringJoiner(", ", ChatSession.class.getSimpleName() + "[", "]")
                .add("id=" + id)
                .add("type=" + type)
                .add("data=" + data)
                .add("unread=" + unread)
                .add("visible=" + visible)
                .add("sticky=" + sticky)
                .add("user=" + user.getId())
                .toString();
    }
}
